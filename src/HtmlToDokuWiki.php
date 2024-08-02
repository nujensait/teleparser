<?php

namespace src;

class HtmlToDokuWiki
{
    public function convert($html)
    {
        // Load HTML using DOMDocument
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $contentDiv = $dom->getElementById('dokuwiki__content');

        if ($contentDiv === null) {
            throw new \Exception("Element with id='dokuwiki__content' not found.");
        }

        $dokuWikiContent = $this->convertNode($contentDiv);

        // Clean output by removing empty lines and leading/trailing spaces
        $dokuWikiContent = $this->cleanOutput($dokuWikiContent);

        // Validate DokuWiki content
        if (!$this->validateDokuWiki($dokuWikiContent)) {
            throw new \Exception("Generated DokuWiki content failed validation.");
        }

        return $dokuWikiContent;
    }

    private function convertNode($node)
    {
        $output = '';
        foreach ($node->childNodes as $child) {
            $output .= $this->convertElement($child);
        }
        return $output;
    }

    private function convertElement($element)
    {
        if ($element instanceof \DOMText) {
            return $this->convertText($element);
        }

        switch ($element->nodeName) {
            case 'h1': return $this->convertHeading($element, '=');
            case 'h2': return $this->convertHeading($element, '==');
            case 'h3': return $this->convertHeading($element, '===');
            case 'h4': return $this->convertHeading($element, '====');
            case 'h5': return $this->convertHeading($element, '=====');
            case 'h6': return $this->convertHeading($element, '======');
            case 'p': return $this->convertParagraph($element);
            case 'br': return "\\\\\n";
            case 'b':
            case 'strong': return $this->convertBold($element);
            case 'i':
            case 'em': return $this->convertItalic($element);
            case 'u': return $this->convertUnderline($element);
            case 'a': return $this->convertLink($element);
            case 'img': return $this->convertImage($element);
            case 'ul': return $this->convertList($element, 'ul');
            case 'ol': return $this->convertList($element, 'ol');
            case 'li': return $this->convertListItem($element);
            case 'table': return $this->convertTable($element);
            case 'tr': return $this->convertTableRow($element);
            case 'td':
            case 'th': return $this->convertTableCell($element);
            case 'pre': return $this->convertPreformatted($element);
            case 'code': return $this->convertCode($element);
            default: return ''; // Ignore other elements
        }
    }

    private function convertText($text)
    {
        return htmlspecialchars($text->wholeText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function convertHeading($element, $level)
    {
        return $level . ' ' . $this->convertNode($element) . ' ' . $level . "\n\n";
    }

    private function convertParagraph($element)
    {
        return $this->convertNode($element) . "\n\n";
    }

    private function convertBold($element)
    {
        return '**' . $this->convertNode($element) . '**';
    }

    private function convertItalic($element)
    {
        return '//' . $this->convertNode($element) . '//';
    }

    private function convertUnderline($element)
    {
        return '__' . $this->convertNode($element) . '__';
    }

    private function convertLink($element)
    {
        $href = $element->getAttribute('href');
        $text = $this->convertNode($element);
        return '[[' . $href . '|' . $text . ']]';
    }

    private function convertImage($element)
    {
        $src = $element->getAttribute('src');
        $alt = $element->getAttribute('alt');
        return '{{' . $src . '|' . $alt . '}}';
    }

    private function convertList($element, $type)
    {
        $output = '';
        foreach ($element->childNodes as $child) {
            if ($child->nodeName == 'li') {
                $output .= ($type == 'ul' ? '  * ' : '  - ') . $this->convertNode($child) . "\n";
            }
        }
        return $output . "\n";
    }

    private function convertListItem($element)
    {
        return $this->convertNode($element);
    }

    private function convertTable($element)
    {
        $output = "\n";
        foreach ($element->childNodes as $child) {
            if ($child->nodeName == 'tr') {
                $output .= $this->convertNode($child);
            }
        }
        return $output . "\n";
    }

    private function convertTableRow($element)
    {
        $output = "|";
        foreach ($element->childNodes as $child) {
            if ($child->nodeName == 'td' || $child->nodeName == 'th') {
                $output .= ' ' . $this->convertNode($child) . ' |';
            }
        }
        return $output . "\n";
    }

    private function convertTableCell($element)
    {
        return $this->convertNode($element);
    }

    private function convertPreformatted($element)
    {
        return '<code>' . htmlspecialchars($element->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>';
    }

    private function convertCode($element)
    {
        return '`' . htmlspecialchars($element->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '`';
    }

    private function cleanOutput($output)
    {
        // Remove empty lines and leading/trailing spaces
        $output = preg_replace("/^\s+|\s+$/m", '', $output);
        $output = preg_replace("/\n{2,}/", "\n", $output);
        return trim($output);
    }

    private function validateDokuWiki($content)
    {
        // Add additional checks for DokuWiki syntax validity here
        // For example, check for unclosed tags or invalid links
        return true;
    }
}

// Пример использования:
//$html = file_get_contents('/mnt/data/manual.html');
//$converter = new HtmlToDokuWiki();
//try {
//    $dokuWikiText = $converter->convert($html);
//    echo $dokuWikiText;
//} catch (Exception $e) {
//    echo 'Ошибка: ' . $e->getMessage();
//}
