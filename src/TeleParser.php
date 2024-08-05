<?php

namespace src;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/HtmlToDokuWiki.php';

use Goutte\Client;
use src\HtmlToDokuWiki;
use Symfony\Component\DomCrawler\Crawler;

class TeleParser
{
    const STATUS_INIT   = 'init';
    const STATUS_START  = 'start';
    const STATUS_FINISH = 'finish';
    const STATUS_SKIP   = 'skip';

    private $baseDir = 'downloads';         // directory to save htmls
    private $parsedUrls = [];               // parsed links array

    private $db;                            // SQLite DB connection

    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;
        $this->initDatabase();
    }

    public function __destruct()
    {
        $this->db->close();
    }

    /**
     * @return void
     */
    private function initDatabase()
    {
        $this->db = new \SQLite3('db/teleparser.db');
        $this->db->exec('CREATE TABLE IF NOT EXISTS parsing (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            start_time DATETIME,
            finish_time DATETIME,
            url TEXT,
            file TEXT,
            depth INTEGER,
            pattern TEXT,   
            status VARCHAR(20),
            message TEXT
        )');
    }

    /**
     * @param array $params
     * Structure: [url, pattern, depth, limit, visited, div]
     *
     * @return void
     */
    public function downloadPage($params)
    {
        $depth      = $params['depth']   ?? 0;
        $limit      = $params['limit']   ?? 0;
        $url        = $params['url']     ?? '';
        $pattern    = $params['pattern'] ?? '';
        $visited    = $params['visited'] ?? [];
        $div        = $params['div']     ?? '';

        // too deep?
        if ($depth < 0) {
            return;
        }

        $parsingId = $this->insertParsingStart($url, $depth, $pattern);

        // already parsed?
        if(in_array($url, $visited) || in_array($url, $this->parsedUrls)) {
            $this->updateParsing($parsingId, ['status' => self::STATUS_SKIP, 'message' => 'Already parsed.']);
            return;
        }

        // amount of visited pages is limited
        if(count($visited) > $limit) {
            return;
        }

        $visited[$url]  = $url;
        $client         = new Client();
        $crawler        = $client->request('GET', $url);

        $html           = $crawler->html();
        $parsedUrl      = parse_url($url);
        $domain         = (isset($parsedUrl['scheme']) ? '://' . $parsedUrl['scheme'] : '') . (isset($parsedUrl['host']) ? $parsedUrl['host'] : '');
        $path           = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        $localPathHtml  = $this->baseDir . '/html' . $path . '.html';
        $localPathWiki  = $this->baseDir . '/dokuwiki' . $path . '.txt';

        // create dirs for HTML
        if (substr($localPathHtml, -1) === '/') {
            $localPathHtml .= 'index.html';
        }

        $localDirHtml = dirname($localPathHtml);

        if (!file_exists($localDirHtml)) {
            if (!mkdir($localDirHtml, 0777, true) && !is_dir($localDirHtml)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDirHtml));
            }
        }

        // Same for wiki
        if (substr($localPathWiki, -1) === '/') {
            $localPathWiki .= 'index.txt';
        }

        $localDirWiki = dirname($localPathWiki);

        if (!file_exists($localDirWiki)) {
            if (!mkdir($localDirWiki, 0777, true) && !is_dir($localDirWiki)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDirWiki));
            }
        }

        // replace links from relative to absolute
        $domain = $this->getDomainFromUrl($url);
        $html = $this->convertRelativeToAbsoluteLinks($html, $domain);

        // Download and replace external resources
        $html = $this->downloadAndReplaceResources($crawler, $domain, $localDirHtml, $html);

        // Generate DokuWiki page
        $converter = new HtmlToDokuWiki();
        try {
            $wiki = $converter->convert($html, $div);
        } catch (Exception $e) {
            echo 'Ошибка: ' . $e->getMessage() . "<br />";
            $this->log('Ошибка: ' . $e->getMessage());
        }

            // Download html
        //$this->downloadResources($crawler, $domain, $localDir);

        // Save the modified HTML
        $resSave = file_put_contents($localPathHtml, $html);
        if($resSave === false) {
            throw new \RuntimeException(sprintf('Cannot save file: "%s"', $localPathHtml));
        }

        // Also save dokuwiki file
        $resSave = file_put_contents($localPathWiki, $wiki);
        if($resSave === false) {
            throw new \RuntimeException(sprintf('Cannot save file: "%s"', $localPathWiki));
        }

        $this->updateParsing($parsingId, ['file' => $localPathHtml]);

        $this->parsedUrls[] = $url;

        // Process internal links
        $crawler->filter('a')->each(function (Crawler $node) use ($domain, $pattern, $depth, &$visited, $div, $limit) {
            $link = $node->attr('href');
            $this->log("[ " . $link . " ]");
            // parse links only from the same domain
            if ($link && (strpos($link, $domain) === 0 || substr($link, 0, 1) === '/')) {
                // if pattern isset, filter links by pattern
                if($pattern && strpos($link, $pattern) === false) {
                    $this->log(" - SKIP (by pattern)\n");
                    return;
                }
                $this->log( " - QUEUED ...\n");
                $params = [
                    'url'       => $link,
                    'pattern'   => $pattern,
                    'depth'     => $depth - 1,
                    'limit'     => $limit,
                    'visited'   => $visited,
                    'div'       => $div
                ];
                $this->downloadPage($params);
            } else {
                $this->log(" - SKIP (by domain)\n");
            }
        });

        $this->updateParsingFinish($parsingId);

        // Replace links in HTML
        //$html = $this->replaceLinks($html, $baseDir, $domain);
        //file_put_contents($localPath, $html);
    }

    /**
     * Store parsing into DB
     * @param $url
     * @param $depth
     * @param $pattern
     *
     * @return mixed
     */
    private function insertParsingStart($url, $depth, $pattern)
    {
        $stmt = $this->db->prepare('INSERT INTO parsing (start_time, url, depth, pattern) VALUES (:start_time, :url, :depth, :pattern)');
        $stmt->bindValue(':start_time', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->bindValue(':depth', $depth, SQLITE3_INTEGER);
        $stmt->bindValue(':pattern', $pattern, SQLITE3_TEXT);
        $stmt->bindValue(':status', self::STATUS_START, SQLITE3_TEXT);

        $stmt->execute();

        return $this->db->lastInsertRowID();
    }

    /**
     * Update parsing status
     * @param $id
     *
     * @return void
     */
    private function updateParsingFinish($id)
    {
        $stmt = $this->db->prepare('UPDATE parsing SET finish_time = :finish_time, status = :status WHERE id = :id');

        $stmt->bindValue(':finish_time', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':status', self::STATUS_FINISH, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

        $stmt->execute();
    }

    /**
     * @param int   $id
     * @param array $fields
     *
     * @return void
     */
    private function updateParsing(int $id, array $fields)
    {
        $setters = [];
        foreach($fields as $k => $v) {
            $setters[] = "$k = :$k";
        }
        $setters = implode(", ", $setters);
        $stmt = $this->db->prepare("UPDATE parsing SET {$setters} WHERE id = :id");

        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        foreach($fields as $k => $v) {
            $stmt->bindValue(':' . $k, $v, SQLITE3_TEXT);
        }

        $stmt->execute();
    }

    /**
     * Log debug message
     * @param $message
     *
     * @return bool
     */
    private function log($message)
    {
        $logFile = fopen($this->baseDir . "/parsing_log.txt", "a");
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

    /**
     * Make absolute links in html
     * @param string $html
     * @param string $domain
     *
     * @return string
     */
    public function convertRelativeToAbsoluteLinks(string $html, string $domain): string
    {
        // Убедимся, что домен заканчивается на слеш
        $domain = rtrim($domain, '/') . '/';

        // Заменяем ссылки в href атрибутах
        $html = preg_replace_callback(
            '/\shref=(["\'])(.+?)\1/i',
            function($matches) use ($domain) {
                return ' href=' . $matches[1] . $this->convertUrl($matches[2], $domain) . $matches[1];
            },
            $html
        );

        // Заменяем ссылки в src атрибутах
        $html = preg_replace_callback(
            '/\ssrc=(["\'])(.+?)\1/i',
            function($matches) use ($domain) {
                return ' src=' . $matches[1] . $this->convertUrl($matches[2], $domain) . $matches[1];
            },
            $html
        );

        return $html;
    }

    /**
     * @param string $url
     * @param string $domain
     *
     * @return string
     */
    public function convertUrl(string $url, string $domain): string
    {
        // Проверяем, является ли URL относительным
        if (substr($url, 0, 2) === '//' || preg_match("~^(?:f|ht)tps?://~i", $url)) {
            return $url; // URL уже абсолютный
        }

        if ($url[0] === '/') {
            return $domain . ltrim($url, '/');
        }

        return $domain . $url;
    }

    /**
     * Read domain from URL
     * @param $url
     *
     * @return string|false
     */
    function getDomainFromUrl($url)
    {
        // Добавляем схему, если её нет
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        $urlParts = parse_url($url);

        // Проверяем, есть ли хост в распарсенном URL
        if (isset($urlParts['host'])) {
            // Удаляем 'www.' если оно присутствует
            return ($urlParts['sheme'] ?? 'http') . '://' . preg_replace('/^www\./', '', $urlParts['host']);
        }

        return false; // Возвращаем false, если домен не найден
    }
}
