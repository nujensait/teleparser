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
        $this->assertEquals($expected, $this->converter->convert($html));
    }

    public function testConvertParagraph()
    {
        $html = '<div id="dokuwiki__content"><p>This is a sample paragraph.</p></div>';
        $expected = "This is a sample paragraph.";
        $this->assertEquals($expected, $this->converter->convert($html));
    }

    public function testConvertBoldText()
    {
        $html = '<div id="dokuwiki__content"><p>This is <b>bold</b> text.</p></div>';
        $expected = "This is **bold** text.";
        $this->assertEquals($expected, $this->converter->convert($html));
    }

    public function testConvertItalicText()
    {
        $html = '<div id="dokuwiki__content"><p>This is <i>italic</i> text.</p></div>';
        $expected = "This is //italic// text.";
        $this->assertEquals($expected, $this->converter->convert($html));
    }

    public function testConvertUnderlineText()
    {
        $html = '<div id="dokuwiki__content"><p>This is <u>underlined</u> text.</p></div>';
        $expected = "This is __underlined__ text.";
        $this->assertEquals($expected, $this->converter->convert($html));
    }

    public function testConvertLink()
    {
        $html = '<div id="dokuwiki__content"><a href="https://example.com">Sample Link</a></div>';
        $expected = "[[https://example.com|Sample Link]]";
        $this->assertEquals($expected, $this->converter->convert($html));
    }

    public function testConvertImage()
    {
        $html = '<div id="dokuwiki__content"><img src="image.jpg" alt="Sample Image" /></div>';
        $expected = "{{image.jpg|Sample Image}}";
        $this->assertEquals($expected, $this->converter->convert($html));
    }

    public function testElementNotFound()
    {
        $html = '<div><h1>Heading outside target div</h1></div>';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Element with id='dokuwiki__content' not found.");
        $this->converter->convert($html);
    }

    /**
     * @return void
     */
    public function testFullHtmlConversion()
    {
        $converter  = new HtmlToDokuWiki();
        $html       = file_get_contents(__DIR__ . '/testHtml.html');
        $expected   = file_get_contents(__DIR__ . '/testWiki.txt');

        $dokuWikiText = $converter->convert($html);
        file_put_contents(__DIR__ . "/factWiki.txt", $dokuWikiText);

        $this->assertEquals($expected, $dokuWikiText);
    }
}
