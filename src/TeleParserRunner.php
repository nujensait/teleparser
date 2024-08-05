<?php

require_once __DIR__ . '/TeleParser.php';

use src\TeleParser;

class TeleParserRunner
{
    /**
     * Html site parsing action
     * @return void
     */
    public function runParsing()
    {
        // params
        $url      = trim($_POST['url'] ?? '');
        $depth    = (int)($_POST['depth'] ?? 0);
        $limit    = (int)($_POST['limit'] ?? 0);
        $pattern  = trim($_POST['pattern'] ?? '');
        $div      = trim($_POST['div'] ?? '');

        // calc
        $baseDir  = 'downloads';
        $baseDir .= "/" . date("Ymd_His") . "_" . getDomainFromUrl($url);
        $parser   = new TeleParser($baseDir);

        echo '<div class="container">';
        echo '<h3>Лог парсинга</h3>';

        echo "<ol>";
        echo "<li><p>Начало парсинга ...</p></li>\n";
        if(is_dir($baseDir)) {
            $parser->utils->deleteDirectory($baseDir);
            echo "<li><p>Удалена старая папка с файлами от предыдущего парсинга: <br /><pre>" . $baseDir . "</pre></p></li>\n";
        }

        // Do parsing
        try {
            $params = [
                'url'       => $url,
                'pattern'   => $pattern,
                'depth'     => $depth,
                'limit'     => $limit,
                'visited'   =>  [],
                'div'       => $div
            ];
            $parser->downloadPage($params);
        } catch(\Throwable $e) {
            echo "Ошибка парсинга. Детали: " . $e->getMessage();
        }

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

    /**
     * Html to dokuWiki conversion
     * @return void
     * @throws Exception
     */
    public function runConvert()
    {
        // params
        $html    = trim($_POST['html'] ?? '');
        $div     = trim($_POST['divid'] ?? '');

        $conv = new \src\HtmlToDokuWiki();

        try {
            $wiki = $conv->convert($html, $div);
        } catch(\Throwable $e) {
            $wiki = "Ошибка конвертации: " . $e->getMessage();
        }

        echo '<h3>Результат конвертации</h3>';
        echo '<div class="input-field">';
        echo '<textarea id="wiki" name="wiki" class="codeText">' . htmlspecialchars($wiki, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .'</textarea>';
        echo '<label for="wiki">Код для dokuWiki:</label>';
        echo '</div>';
        echo '<button class="btn waves-effect waves-light" type="button" id="copyButton">Копировать в буфер';
        echo '<i class="material-icons right">content_copy</i>';
        echo '</button>';
    }
}