<?php

namespace src;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/HtmlToDokuWiki.php';
require_once __DIR__ . '/HtmlUtils.php';

use src\HtmlToDokuWiki;
use src\HtmlUtils;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class TeleParser
{
    const STATUS_INIT   = 'init';
    const STATUS_START  = 'start';
    const STATUS_FINISH = 'finish';
    const STATUS_SKIP   = 'skip';

    const ALLOWED_FIELDS = ['start_time', 'finish_time', 'url', 'file', 'status', 'message'];

    private string $baseDir = 'downloads';          // directory to save htmls
    private array $parsedUrls = [];                 // parsed links array
    private \SQLite3 $db;                           // SQLite DB connection
    public HtmlUtils $utils;                        // Utils class (some functions)
    private int $run_id;                            // ID of current pasring process

    /**
     * On start
     * @param $baseDir
     */
    public function __construct($baseDir)
    {
        $this->initDatabase();

        $this->baseDir = $baseDir;
        $this->utils = new HtmlUtils();
    }

    /**
     * On finish
     */
    public function __destruct()
    {
        if($this->db) {
            $this->db->close();
        }
    }

    /**
     * Crteate tables
     * @return void
     */
    private function initDatabase()
    {
        $this->db = new \SQLite3('db/teleparser.db');

        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS runs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                start_time DATETIME,
                finish_time DATETIME,
                start_url TEXT,
                depth INTEGER,
                pages_limit INTEGER,
                pattern TEXT,   
                status VARCHAR(20),
                message TEXT
            )');

            $this->db->exec('CREATE TABLE IF NOT EXISTS parsing (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                start_time DATETIME,
                finish_time DATETIME,
                url TEXT,
                file TEXT,
                status VARCHAR(20),
                message TEXT,
                run_id INTEGER,
                parent_id INTEGER,
                type VARCHAR(20)
            )');
        } catch(\Throwable $e) {
            $msg = "Error in creating DB tables";
            $this->log($msg);
        }
    }

    /**
     * @param array $params
     * Structure: [url, pattern, depth, limit, visited, div]
     *
     * @return void
     */
    public function downloadPage($params)
    {
        $url        = $params['url']     ?? '';
        $pattern    = $params['pattern'] ?? '';
        $depth      = $params['depth']   ?? 0;
        $limit      = $params['limit']   ?? 0;
        $visited    = $params['visited'] ?? [];
        $div        = $params['div']     ?? '';

        // too deep?
        if ($depth < 0) {
            return;
        }

        $parsingId = $this->startPageParsing($url);

        // already parsed?
        if(in_array($url, $visited) || in_array($url, $this->parsedUrls)) {
            $this->updatePageParsing($parsingId, ['status' => self::STATUS_SKIP, 'message' => 'Already parsed.']);
            return;
        }

        // amount of visited pages is limited
        if($limit > 0 && count($visited) > $limit) {
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

        if (!file_exists($localDirHtml) && !mkdir($localDirHtml, 0777, true) && !is_dir($localDirHtml)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDirHtml));
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
        $domain = $this->utils->getDomainFromUrl($url);
        $html = $this->utils->convertRelativeToAbsoluteLinks($html, $domain);

        // Download and replace external resources
        $html = $this->downloadAndReplaceResources($crawler, $domain, $localDirHtml, $html);

        // Generate DokuWiki page
        $converter = new HtmlToDokuWiki();
        $wiki = '';
        try {
            $wiki = $converter->convert($html, $div);
        } catch (\Exception $e) {
            $msg = 'Ошибка: ' . $e->getMessage();
            echo $msg . "<br />";
            $this->updatePageParsing($parsingId, ['message' => $msg]);
            $this->log($msg . "\n");
        }

        // Download html
        //$this->downloadResources($crawler, $domain, $localDirHtml);

        // Save the modified HTML
        if($html) {
            $resSave = file_put_contents($localPathHtml, $html);
            if($resSave === false) {
                $msg = sprintf('Cannot save file: "%s"', $localPathHtml);
                $this->updatePageParsing($parsingId, ['message' => $msg]);
                throw new \RuntimeException($msg);
            }
        }

        // Also save dokuwiki file
        if($wiki) {
            $resSave = file_put_contents($localPathWiki, $wiki);
            if($resSave === false) {
                $msg = sprintf('Cannot save file: "%s"', $localPathWiki);
                $this->updatePageParsing($parsingId, ['message' => $msg]);
                throw new \RuntimeException($msg);
            }
        }

        $this->updatePageParsing($parsingId, ['file' => $localPathHtml]);

        $this->parsedUrls[$url] = $url;
        $cntPages = 0;
        $breakProcess = false;

        //die('ssss');

        // Process internal links/pages
        $crawler->filter('a')->each(function (Crawler $node) use ($domain, $pattern, $depth, &$visited, $div, $limit, &$cntPages, &$breakProcess) {

            if($breakProcess) {
                return;
            }

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
                $cntPages++;
                if($limit > 0 && $cntPages > $limit) {
                    $breakProcess = true;
                }
            } else {
                $this->log(" - SKIP (by domain)\n");
            }
        });

        $this->finishPageParsing($parsingId);

        // Replace links in HTML
        //$html = $this->replaceLinks($html, $baseDir, $domain);
        //file_put_contents($localPath, $html);
    }

    /**
     * Memory parsing start process
     * @return void
     */
    public function startParsing(array $params): int
    {
        // save run
        $stmt = $this->db->prepare('INSERT INTO runs (start_time, start_url, depth, pages_limit, pattern, status) ' .
                                         'VALUES (:start_time, :start_url, :depth, :pages_limit, :pattern, :status)');

        $stmt->bindValue(':start_time', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':start_url', $params['url'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':depth', $params['depth'] ?? 0, SQLITE3_INTEGER);
        $stmt->bindValue(':pages_limit', $params['pages_limit'] ?? 0, SQLITE3_INTEGER);
        $stmt->bindValue(':pattern', $params['pattern'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':status', $params['status'] ?? self::STATUS_INIT, SQLITE3_TEXT);

        $stmt->execute();

        $this->run_id = $this->db->lastInsertRowID();

        return $this->run_id;
    }

    /**
     * Finish parsing process
     * @param array $params
     *
     * @return bool
     */
    public function finishParsing(array $params): bool
    {
        // save run
        $stmt = $this->db->prepare('UPDATE runs SET finish_time = :finish_time, status = :status WHERE id = :id');

        $stmt->bindValue(':finish_time', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':status', self::STATUS_FINISH, SQLITE3_TEXT);
        $stmt->bindValue(':id', $this->run_id, SQLITE3_INTEGER);

        $stmt->execute();

        return true;
    }

    /**
     * Store parsing into DB
     * @param $url
     *
     * @return int
     */
    private function startPageParsing($url): int
    {
        $stmt = $this->db->prepare('INSERT INTO parsing (start_time, url) VALUES (:start_time, :url)');

        $stmt->bindValue(':start_time', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->bindValue(':status', self::STATUS_START, SQLITE3_TEXT);
        $stmt->bindValue(':run_id', $this->run_id, SQLITE3_INTEGER);

        $stmt->execute();

        return $this->db->lastInsertRowID();
    }

    /**
     * Update parsing status
     * @param $id
     *
     * @return void
     */
    private function finishPageParsing($id)
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
    private function updatePageParsing(int $id, array $fields)
    {
        // check allowed fields
        foreach($fields as $k => $v) {
            if(!in_array($k, self::ALLOWED_FIELDS)) {
                throw new \Exception("Unknown field: " . $k);
            }
        }

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
     * Download external resources and update the HTML to use local file paths.
     *
     * @param Crawler $crawler The crawler instance.
     * @param string $domain The domain of the current URL.
     * @param string $localFileDir The local directory to save the resources.
     * @param string $html The HTML content to update.
     * @return string The updated HTML content.
     */
    private function downloadAndReplaceResources(Crawler $crawler, $domain, $localFileDir, $html)
    {
        $resources = [];
        $crawler->filter('link[rel="stylesheet"], script[src], img[src]')->each(function (Crawler $node) use (&$resources, $domain) {
            $tag = $node->nodeName();
            $attr = ($tag === 'link') ? 'href' : 'src';
            $url = $node->attr($attr);
            //echo "<pre>"; var_dump($url . '<==>' . $domain); var_dump($url && strpos($url, $domain) === 0); die();

            if (1) { // $url && strpos($url, $domain) === 0) {      // skip domain check: assets can be external
                $resources[$url] = $url;
            }
        });

        foreach ($resources as $resourceUrl) {

            // determine resource type
            $fileType = $this->utils->getFileTypeFromUrl($resourceUrl);
            if(!in_array($fileType, ['css', 'js', 'image'])) {
                continue;   // skip file
            }

            $localPath = $this->baseDir . '/html/' . $fileType . '/' . basename(parse_url($resourceUrl, PHP_URL_PATH));

            // save as debug
            $this->log("- Save assets file: " . $resourceUrl . ' to ' . $localPath . "\n");

            $localDir = dirname($localPath);
            if (!file_exists($localDir) && !mkdir($localDir, 0777, true) && !is_dir($localDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDir));
            }

            $content = @file_get_contents($resourceUrl);

            // if content is not empty
            if($content) {
                // save resource locally
                file_put_contents($localPath, $content);

                $dirsCount = $this->utils->countDirectoriesInPath($localPath) + 1;
                $localDirs = implode("", array_fill(0, $dirsCount, '../'));
                $localUrl = $localDirs . 'html/' . $fileType . '/' . basename($localPath);

                // Replace URLs in the HTML content
                $html = str_replace($resourceUrl, $localUrl, $html);
            }
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
            if (!file_exists($localDir) && !mkdir($localDir, 0777, true) && !is_dir($localDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDir));
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
     * Log debug message
     * @param $message
     *
     * @return bool
     */
    private function log($message)
    {
        $logFile = fopen($this->baseDir . "/parsing_log.txt", "a");

        if($logFile) {
            if(mb_substr($message, -1) !== "\n") {      // add "\n" at the end
                $message .= "\n";
            }
            fwrite($logFile, $message);
            fclose($logFile);
            return true;
        }

        return false;
    }
}
