<?php

require_once ("TeleParser.php");

/**
 * Run parsing
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $parser   = new TeleParser();
    $url      = trim($_POST['url']);
    $depth    = (int)$_POST['depth'];
    $baseDir  = 'downloads';
    $baseDir .= "/" . $parser->getDomainFromUrl($url);

    echo '<div class="container">';

    echo "<p>Начало парсинга ...</p><br />\n";
    if(is_dir($baseDir)) {
        $parser->deleteDirectory($baseDir);
        echo "<p>Удалена старая папка с файлами от предыдущего парсинга: <br />" . $baseDir . "</p><br />\n";
    }

    // Do parsing
    $parser->downloadPage($url, $depth, $baseDir);

    echo "<p>Парсинг успешно выполнен.</p><br />\n";

    // Display links to downloaded files
    echo '<h3>Скачанные файлы</h3>';
    echo '<ul>';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = str_replace('\\', '/', $file->getPathname());
            echo '<li><a href="' . $filePath . '" target="_blank">' . $filePath . '</a></li>';
        }
    }
    echo '</ul>';
    echo '</div><!-- "container" -->';
}
