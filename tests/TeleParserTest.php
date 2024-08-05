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
use src\TeleParser;

class TeleParserTest extends TestCase
{
    /**
     * @var TeleParser
     */
    protected $parser;


    private $baseDir;
    
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->baseDir = __DIR__ . '/downloads/teleparser_test_' . uniqid();

        mkdir($this->baseDir);

        $this->parser = new TeleParser($this->baseDir);
    }

    /**
     * Test convert links to absolute
     */
    public function testConvertRelativeToAbsoluteLinks()
    {
        $html     = '<a href="/page">Link</a><img src="/assets/images/parser.jpg" alt="alt">';
        $domain   = 'https://example.com';
        $result   = $this->parser->convertRelativeToAbsoluteLinks($html, $domain);

        $expected = '<a href="https://example.com/page">Link</a><img src="https://example.com/assets/images/parser.jpg" alt="alt">';

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->parser->deleteDirectory($this->baseDir);
    }

    /**
     * @return void
     */
    public function testConvertUrl()
    {
        $domain = 'https://example.com/';

        $this->assertEquals('https://example.com/page', $this->parser->convertUrl('/page', $domain));
        $this->assertEquals('https://example.com/image.jpg', $this->parser->convertUrl('image.jpg', $domain));
        $this->assertEquals('https://other.com/page', $this->parser->convertUrl('https://other.com/page', $domain));
    }

    /**
     * @return void
     */
    public function fixme_testDeleteDirectory()
    {
        $testDir = $this->baseDir . '/delete_test';
        mkdir($testDir);
        file_put_contents($testDir . '/test.txt', 'test content');

        $this->assertTrue(file_exists($testDir));
        $this->assertTrue(file_exists($testDir . '/test.txt'));

        $this->parser->deleteDirectory($testDir);

        $this->assertFalse(file_exists($testDir));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function fixme_testInitDatabase()
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
