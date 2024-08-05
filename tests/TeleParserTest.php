<?php

/**
 * Test TeleParser class
 * Usage:
 * phpunit tests/TeleParserTest.php
 * phpunit tests/TeleParserTest.php --filter testConvertRelativeToAbsoluteLinks
 */

require_once __DIR__ . '/../src/TeleParser.php';

use PHPUnit\Framework\TestCase;
use src\TeleParser;

class TeleParserTest extends TestCase
{
    /**
     * @var TeleParser
     */
    protected $parser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $baseDir  = __DIR__ . '/downloads';

        $this->parser = new TeleParser($baseDir);
    }

    /**
     * Test convert links to absolute
     */
    public function testConvertRelativeToAbsoluteLinks()
    {
        $html     = '<a href="/page">Link</a><img src="image.jpg">';
        $domain   = 'https://example.com';
        $result   = $this->parser->convertRelativeToAbsoluteLinks($html, $domain);

        $expected = '<a href="https://example.com/page">Link</a><img src="https://example.com/image.jpg">';

        $this->assertEquals($expected, $result);
    }
}