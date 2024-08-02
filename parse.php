<?php

/**
 * Start script
 */

require_once __DIR__ . '/src/TeleParser.php';
require_once __DIR__ . '/src/functions.php';

use src\TeleParser;

// Set the maximum execution time to 300 seconds (5 minutes)
set_time_limit(60 * 10);    // timeout 10 min

/**
 * Run parsing
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // params
    $url      = trim($_POST['url']);
    $depth    = (int)$_POST['depth'];
    $pattern  = trim($_POST['pattern']);
    $div      = trim($_POST['div']);

    // calc
    $baseDir  = 'downloads';
    $baseDir .= "/" . date("Ymd_His") . "_" . getDomainFromUrl($url);
    $parser   = new TeleParser($baseDir);

    echo '<div class="container">';
    echo '<h3>Лог парсинга</h3>';

    echo "<ol>";
    echo "<li><p>Начало парсинга ...</p></li>\n";
    if(is_dir($baseDir)) {
        $parser->deleteDirectory($baseDir);
        echo "<li><p>Удалена старая папка с файлами от предыдущего парсинга: <br /><pre>" . $baseDir . "</pre></p></li>\n";
    }

    // Do parsing
    $parser->downloadPage($url, $pattern, $depth, [], $div);

    echo "<li><p>Парсинг успешно выполнен.</p></li>\n";
    echo "<li><p>Все файлы сохранены в папку: <br /><pre>{$baseDir}</pre></p></li>\n";
    echo "</ol>";

    // Display links to downloaded files
    echo '<h3>Скачанные файлы</h3>';
    echo '<ol>';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = str_replace('\\', '/', $file->getPathname());
            echo '<li><a href="' . $filePath . '" target="_blank">' . $filePath . '</a></li>';
        }
    }
    echo '</ol>';
    echo '</div><!-- "container" -->';
}
