<?php

/**
 * Test HtmlToDokuWiki class
 * Usage:
 * phpunit tests/HtmlToDokuWikiTest.php
 * phpunit tests/HtmlToDokuWikiTest.php --filter testFullHtmlConversion
 */

namespace Tests;

require_once __DIR__ . '/../src/HtmlToDokuWiki.php';

use PHPUnit\Framework\TestCase;
use src\HtmlToDokuWiki;

class HtmlToDokuWikiTest extends TestCase
{
    /**
     * @var HtmlToDokuWiki
     */
    protected $converter;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->converter = new HtmlToDokuWiki();
    }

    /**
     * @return void
     */
    public function testConvertHeading()
    {
        $html = '<div id="dokuwiki__content"><h1>Sample Heading</h1></div>';
        $expected = "= Sample Heading =";

        try {
            $fact = $this->converter->convert($html, 'dokuwiki__content');
        } catch(\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    /**
     * @return void
     */
    public function testConvertParagraph()
    {
        $html = '<div id="dokuwiki__content"><p>This is a sample paragraph.</p></div>';
        $expected = "This is a sample paragraph.";

        try {
            $fact = $this->converter->convert($html, 'dokuwiki__content');
        } catch(\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    /**
     * @return void
     */
    public function testConvertBoldText()
    {
        $html = '<div id="dokuwiki__content"><p>This is <b>bold</b> text.</p></div>';
        $expected = "This is **bold** text.";

        try {
            $fact = $this->converter->convert($html, 'dokuwiki__content');
        } catch(\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    /**
     * @return void
     */
    public function testConvertItalicText()
    {
        $html = '<div id="dokuwiki__content"><p>This is <i>italic</i> text.</p></div>';
        $expected = "This is //italic// text.";

        try {
            $fact = $this->converter->convert($html, 'dokuwiki__content');
        } catch(\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    /**
     * @return void
     */
    public function testConvertUnderlineText()
    {
        $html = '<div id="dokuwiki__content"><p>This is <u>underlined</u> text.</p></div>';
        $expected = "This is __underlined__ text.";

        try {
            $fact = $this->converter->convert($html, 'dokuwiki__content');
        } catch(\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    /**
     * @return void
     */
    public function fixme_testConvertLink()
    {
        $html = '<a href="https://example.com">Sample Link</a>';
        $expected = "[[https://example.com|Sample Link]]";

        try {
            $fact = $this->converter->convert($html, '');
        } catch(\Throwable $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    /**
     * @return void
     */
    public function testConvertImage()
    {
        $html = '<div id="dokuwiki__content"><img src="/assets/images/parser.jpg" alt="Sample Image" /></div>';
        $expected = "{{/assets/images/parser.jpg|Sample Image}}";

        try {
            $fact = $this->converter->convert($html, 'dokuwiki__content');
        } catch(\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testElementNotFound()
    {
        $html = '<div><h1>Heading outside target div</h1></div>';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Element with id='dokuwiki__content' not found.");

        $this->converter->convert($html, 'dokuwiki__content');
    }

    /**
     * Convert long html to DokuWiki & check conversion result
     * @return void
     */
    public function testFullHtmlConversion()
    {
        $converter  = new HtmlToDokuWiki();
        $html       = file_get_contents(__DIR__ . '/testHtml.html');
        $expected   = file_get_contents(__DIR__ . '/testWiki.txt');

        $dokuWikiText = $converter->convert($html, 'dokuwiki__content');
        file_put_contents(__DIR__ . "/factWiki.txt", $dokuWikiText);

        $this->assertEquals($expected, $dokuWikiText);
    }
}
