<?php

/**
 * Starting script
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

    $action = $_REQUEST['todo'] ?? null;
    $runner = new TeleParserRunner();

    switch($action) {
        case 'parsing':
            $runner->runParsing();
            break;
        case 'convert':
            $runner->runConvert();
            break;
        default:
            echo "<p style='color: red;'>Ошибка: выбрано неизвеcтное действие.</p>";
    }
}
