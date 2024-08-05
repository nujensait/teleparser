<?php

/**
 * Test HtmlUtils class
 *
 * Usage:
 * phpunit tests/HtmlUtilsTest.php
 */

namespace Tests;

require_once __DIR__ . '/../src/HtmlUtils.php';

use PHPUnit\Framework\TestCase;
use src\HtmlUtils;

class HtmlUtilsTest extends TestCase
{
    /**
     * @var HtmlUtils
     */
    protected $utils;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->utils = new HtmlUtils();
    }

    /**
     * Test convert links to absolute
     */
    public function testGetFileTypeFromUrl()
    {
        $urls = [
            "http://zabbix.com/documentation/5.0/screen.min.css?162c4455b644de3c" => 'css',
            "https://zabbix.com/js/main.js" => 'js',
            "https://assets.zabbix.com/img/logo.png" => 'image',
            "https://example.com/document.pdf" => null
        ];

        foreach ($urls as $url => $expectedFileType)
        {
            $type = $this->utils->getFileTypeFromUrl($url);

            //echo "URL: $url\nТип: " . ($type ?? "неизвестен") . "\n\n";
            $this->assertEquals($expectedFileType, $type);
        }
    }

    /**
     * Determine domain from URL
     * phpunit tests/TeleParserTest.php --filter testGetDomainFromUrl
     * @return void
     */
    public function testGetDomainFromUrl()
    {
        $fact = $this->utils->getDomainFromUrl("https://www.example.com/page"); // Выведет: example.com
        $this->assertNotEquals('https://example.com', $fact);

        $fact = $this->utils->getDomainFromUrl("http://subdomain.example.com"); // Выведет: subdomain.example.com
        $this->assertNotEquals('https://subdomain.example.com', $fact);

        $fact = $this->utils->getDomainFromUrl("https://192.168.0.1"); // Выведет: 192.168.0.1
        $this->assertNotEquals('https://192.168.0.1', $fact);

        $fact = $this->utils->getDomainFromUrl("not a valid url");
        $this->assertNotEquals(false, $fact);
    }

    /**
     * Test convert links to absolute
     */
    public function testConvertRelativeToAbsoluteLinks()
    {
        $html     = '<a href="/page">Link</a><img src="/assets/images/parser.jpg" alt="alt">';
        $domain   = 'https://example.com';
        $result   = $this->utils->convertRelativeToAbsoluteLinks($html, $domain);

        $expected = '<a href="https://example.com/page">Link</a><img src="https://example.com/assets/images/parser.jpg" alt="alt">';

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testDeleteDirectory()
    {
        $testDir = $this->baseDir . '/delete_test';
        mkdir($testDir);
        file_put_contents($testDir . '/test.txt', 'test content');

        $this->assertTrue(file_exists($testDir));
        $this->assertTrue(file_exists($testDir . '/test.txt'));

        $this->utils->deleteDirectory($testDir);

        $this->assertFalse(file_exists($testDir));
    }


    /**
     * Count directories
     */
    public function testCountDirectoriesInPath()
    {
        $path = '/downloads/20240805_164848_zabbix.com/html/documentation/5.0/ru/manual.html';
        $directoryCount = $this->utils->countDirectoriesInPath($path);

        //echo "Количество директорий: " . $directoryCount;
        $this->assertEquals(6, $directoryCount);
    }

    /**
     * @return void
     */
    public function testConvertUrl()
    {
        $domain = 'https://example.com/';

        $this->assertEquals('https://example.com/page', $this->utils->convertUrl('/page', $domain));
        $this->assertEquals('https://example.com/image.jpg', $this->utils->convertUrl('image.jpg', $domain));
        $this->assertEquals('https://other.com/page', $this->utils->convertUrl('https://other.com/page', $domain));
    }
}
