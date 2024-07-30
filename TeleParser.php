<?php

require 'vendor/autoload.php';

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class TeleParser
{
    private $baseDir = 'downloads';         // directory to save htmls
    private $parsedUrls = [];               // parsed links array

    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    /**
     * @param $url
     * @param $depth
     * @param $visited
     *
     * @return void
     */
    public function downloadPage($url, $pattern, $depth, $visited = [])
    {
        if ($depth < 0 || in_array($url, $visited)) {
            return;
        }

        // already parsed?
        if(in_array($url, $this->parsedUrls)) {
            return;
        }

        $visited[]      = $url;
        $client         = new Client();
        $crawler        = $client->request('GET', $url);

        $html           = $crawler->html();
        $parsedUrl      = parse_url($url);
        $domain         = (isset($parsedUrl['scheme']) ? '://' . $parsedUrl['scheme'] : '') . (isset($parsedUrl['host']) ? $parsedUrl['host'] : '');
        $path           = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        $localPath      = $this->baseDir . $path . '.html';

        if (substr($localPath, -1) === '/') {
            $localPath .= 'index.html';
        }

        $localDir = dirname($localPath);

        if (!file_exists($localDir)) {
            if (!mkdir($localDir, 0777, true) && !is_dir($localDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDir));
            }
        }

        // Download and replace external resources
        $html = $this->downloadAndReplaceResources($crawler, $domain, $localDir, $html);

        // Download html
        //$this->downloadResources($crawler, $domain, $localDir);

        // Save the modified HTML
        file_put_contents($localPath, $html);

        $this->parsedUrls[] = $url;

        // Process internal links
        $crawler->filter('a')->each(function (Crawler $node) use ($domain, $pattern, $depth, &$visited) {
            $link = $node->attr('href');
            $this->log("[ " . $link . " ]");
            // parse links only from the same domain
            if ($link && (strpos($link, $domain) === 0 || substr($link, 0, 1) === '/')) {
                // if pattern isset, filter links by pattern
                if($pattern && strpos($link, $pattern) === false) {
                    $this->log(" - SKIP (by pattern)\n");
                    return;
                }
                $this->log( " - Dowloading ...\n");
                $this->downloadPage($link, $pattern, $depth - 1,  $visited);
            } else {
                $this->log(" - SKIP (by domain)\n");
            }
        });

        // Replace links in HTML
        //$html = $this->replaceLinks($html, $baseDir, $domain);
        //file_put_contents($localPath, $html);
    }

    /**
     * @param $message
     *
     * @return bool
     */
    private function log($message)
    {
        $logFile = fopen($this->baseDir . "/parsing.log", "a");
        if($logFile) {
            fwrite($logFile, $message);
            fclose($logFile);
            return true;
        }

        return false;
    }

    /**
     * Download external resources and update the HTML to use local file paths.
     *
     * @param Crawler $crawler The crawler instance.
     * @param string $domain The domain of the current URL.
     * @param string $localDir The local directory to save the resources.
     * @param string $html The HTML content to update.
     * @return string The updated HTML content.
     */
    private function downloadAndReplaceResources(Crawler $crawler, $domain, $localDir, $html)
    {
        $resources = [];
        $crawler->filter('link[rel="stylesheet"], script[src], img[src]')->each(function (Crawler $node) use (&$resources, $domain) {
            $tag = $node->nodeName();
            $attr = ($tag === 'link') ? 'href' : 'src';
            $url = $node->attr($attr);
            if ($url && strpos($url, $domain) === 0) {
                $resources[] = $url;
            }
        });

        foreach ($resources as $resourceUrl) {
            $localPath = $this->baseDir . parse_url($resourceUrl, PHP_URL_PATH);
            $localDir = dirname($localPath);
            if (!file_exists($localDir)) {
                if (!mkdir($localDir, 0777, true) && !is_dir($localDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDir));
                }
            }

            $content = file_get_contents($resourceUrl);
            file_put_contents($localPath, $content);

            // Replace URLs in the HTML content
            $html = str_replace($resourceUrl, $localPath, $html);
        }

        return $html;
    }

    /**
     * @param Crawler $crawler
     * @param         $domain
     * @param         $localDir
     *
     * @return void
     */
    private function downloadResources(Crawler $crawler, $domain, $localDir)
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
            $localPath = $this->baseDir . parse_url($resourceUrl, PHP_URL_PATH);  // . '.html';
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
     * @param $domain
     *
     * @return mixed
     */
    private function replaceLinks($html, $domain)
    {
        $crawler = new Crawler($html);
        $baseDir = $this->baseDir;

        $crawler->filter('a')->each(function (Crawler $node) use ($baseDir, $domain) {
            $href = $node->attr('href');
            if ($href && strpos($href, $domain) === 0) {
                $localPath = $baseDir . parse_url($href, PHP_URL_PATH); // . '.html';
                $node->getNode(0)->setAttribute('href', $localPath);
            } else {
                $node->getNode(0)->setAttribute('onclick', 'return confirm("Cтраница не скачана, открыть ее в интернете?");');
            }
        });

        return $crawler->html();
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

/**
 * Extracts the domain from a given URL.
 *
 * @param string $url The URL to extract the domain from.
 * @return string The extracted domain.
 */
function getDomainFromUrl($url)
{
    // Parse the URL and get the host component
    $parsedUrl = parse_url($url, PHP_URL_HOST);

    // Remove 'www.' prefix if present
    $domain = preg_replace('/^www\./', '', $parsedUrl);

    return $domain;
}
