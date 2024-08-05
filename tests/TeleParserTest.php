<?php

/**
 * Test TeleParser class
 * Usage:
 * phpunit tests/TeleParserTest.php
 * phpunit tests/TeleParserTest.php --filter testConvertRelativeToAbsoluteLinks
 */

namespace Tests;

require_once __DIR__ . '/../src/TeleParser.php';

use PHPUnit\Framework\TestCase;
use src\HtmlUtils;
use src\TeleParser;

class TeleParserTest extends TestCase
{
    /**
     * @var TeleParser
     */
    protected $parser;

    /**
     * @var HtmlUtils
     */
    protected $utils;

    private $baseDir;
    
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->baseDir = __DIR__ . '/downloads/teleparser_test_' . uniqid();
        $this->utils = new HtmlUtils();

        mkdir($this->baseDir);

        $this->parser = new TeleParser($this->baseDir);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->utils->deleteDirectory($this->baseDir);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testInitDatabase()
    {
        $reflection = new \ReflectionClass($this->parser);
        $method     = $reflection->getMethod('initDatabase');
        $method->setAccessible(true);
        $method->invoke($this->parser);

        $db     = new \SQLite3('db/teleparser_tmp.db');
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='parsing'");
        $resultArray = $result->fetchArray();
        //echo "<pre>"; var_dump($resultArray); die();

        //$this->assertNotFalse($resultArray);
        $this->assertNotEquals(false, $resultArray);

        $db->close();
        unlink('db/teleparser_tmp.db');
    }
}
