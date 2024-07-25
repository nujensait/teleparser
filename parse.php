<?php

/**
 * Run parsing
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $url     = $_POST['url'];
    $depth   = (int)$_POST['depth'];
    $baseDir = 'local_copy';

    $parser = new TeleParser();
    $parser->downloadPage($url, $depth, $baseDir);

    // Display links to downloaded files
    echo '<h2>Скачанные файлы:</h2>';
    echo '<ul>';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = str_replace('\\', '/', $file->getPathname());
            echo '<li><a href="' . $filePath . '" target="_blank">' . $filePath . '</a></li>';
        }
    }
    echo '</ul>';
}
