<?php

require 'vendor/autoload.php';

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class TeleParser
{
    /**
     * @param $url
     * @param $depth
     * @param $baseDir
     * @param $visited
     *
     * @return void
     */
    public function downloadPage($url, $depth, $baseDir, $visited = [])
    {
        if ($depth < 0 || in_array($url, $visited)) {
            return;
        }

        $visited[] = $url;
        $client    = new Client();
        $crawler   = $client->request('GET', $url);

        $html      = $crawler->html();
        $parsedUrl = parse_url($url);
        $domain    = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $path      = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        $localPath = $baseDir . $path . '.html';

        if (substr($localPath, -1) === '/') {
            $localPath .= 'index.html';
        }

        $localDir = dirname($localPath);

        if (!file_exists($localDir)) {
            if (!mkdir($localDir, 0777, true) && !is_dir($localDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDir));
            }
        }

        file_put_contents($localPath, $html);

        // Download CSS, JS, and Images
        $this->downloadResources($crawler, $domain, $localDir, $baseDir);

        // Process internal links
        $crawler->filter('a')->each(function (Crawler $node) use ($domain, $depth, $baseDir, &$visited) {
            $link = $node->attr('href');
            if ($link && strpos($link, $domain) === 0) {
                $this->downloadPage($link, $depth - 1, $baseDir, $visited);
            }
        });

        // Replace links in HTML
        $html = $this->replaceLinks($html, $baseDir, $domain);
        file_put_contents($localPath, $html);
    }

    /**
     * @param Crawler $crawler
     * @param         $domain
     * @param         $localDir
     * @param         $baseDir
     *
     * @return void
     */
    private function downloadResources(Crawler $crawler, $domain, $localDir, $baseDir)
    {
        $resources = [];
        $crawler->filter('link[rel="stylesheet"], script[src], img[src]')->each(function (Crawler $node) use (&$resources, $domain) {
            $tag  = $node->nodeName();
            $attr = ($tag === 'link') ? 'href' : 'src';
            $url  = $node->attr($attr);
            if ($url && strpos($url, $domain) === 0) {
                $resources[] = $url;
            }
        });

        foreach ($resources as $resourceUrl) {
            $localPath = $baseDir . parse_url($resourceUrl, PHP_URL_PATH);
            $localDir  = dirname($localPath);
            if (!file_exists($localDir)) {
                if (!mkdir($localDir, 0777, true) && !is_dir($localDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDir));
                }
            }

            $content = file_get_contents($resourceUrl);
            file_put_contents($localPath, $content);
        }
    }

    /**
     * @param $html
     * @param $baseDir
     * @param $domain
     *
     * @return mixed
     */
    private function replaceLinks($html, $baseDir, $domain)
    {
        $crawler = new Crawler($html);
        $crawler->filter('a')->each(function (Crawler $node) use ($baseDir, $domain) {
            $href = $node->attr('href');
            if ($href && strpos($href, $domain) === 0) {
                $localPath = $baseDir . parse_url($href, PHP_URL_PATH);
                $node->getNode(0)->setAttribute('href', $localPath);
            } else {
                $node->getNode(0)->setAttribute('onclick', 'return confirm("Cтраница не скачана, открыть ее в интернете?");');
            }
        });

        return $crawler->html();
    }

    /**
     * Extracts the domain from a given URL.
     *
     * @param string $url The URL to extract the domain from.
     * @return string The extracted domain.
     */
    public function getDomainFromUrl($url)
    {
        // Parse the URL and get the host component
        $parsedUrl = parse_url($url, PHP_URL_HOST);

        // Remove 'www.' prefix if present
        $domain = preg_replace('/^www\./', '', $parsedUrl);

        return $domain;
    }

    /**
     * Deletes a directory and all its contents (files and subdirectories).
     *
     * @param string $dir The path to the directory to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}

