<?php

/**
 * Test HtmlToDokuWiki class
 * Usage:
 * phpunit tests/HtmlToDokuWikiTest.php
 */

require_once __DIR__ . '/../src/HtmlToDokuWiki.php';

use PHPUnit\Framework\TestCase;
use src\HtmlToDokuWiki;

class HtmlToDokuWikiTest extends TestCase
{
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new HtmlToDokuWiki();
    }

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

    public function testConvertImage()
    {
        $html = '<div id="dokuwiki__content"><img src="image.jpg" alt="Sample Image" /></div>';
        $expected = "{{image.jpg|Sample Image}}";

        try {
            $fact = $this->converter->convert($html, 'dokuwiki__content');
        } catch(\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        $this->assertEquals($expected, $fact);
    }

    public function testElementNotFound()
    {
        $html = '<div><h1>Heading outside target div</h1></div>';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Element with id='dokuwiki__content' not found.");

        $this->converter->convert($html, 'dokuwiki__content');
    }

    /**
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
