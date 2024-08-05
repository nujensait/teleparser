<?php

/**
 * Convert html to DokuWiki
 */

namespace src;

class HtmlToDokuWiki
{
    /**
     * @param string $html
     * @param string $div
     *
     * @return string
     * @throws \Exception
     */
    public function convert(string $html, string $div = '')
    {
        // Загружаем HTML с помощью DOMDocument
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        if($div === '') {
            $body = $dom->getElementsByTagName('body')->item(0);              // full HTML
        } else {
            $body = $dom->getElementById($div);          // content part of HTML
            // also try to find by class:
            if ($body === null) {
                // Find elements by class name
                $elements = $this->findElementsByClass($dom, $div);
                if(is_array($elements) && count($elements)) {
                    $body = $elements[0];
                }
            }
        }

        // nothing found?
        if ($body === null) {
            throw new \Exception("/Элемент html с id='{$div}' не найден.");
        }

        $dokuWikiContent = $this->convertNode($body);

        // Clean output by removing empty lines and leading/trailing spaces
        $dokuWikiContent = $this->cleanOutput($dokuWikiContent);

        // Validate DokuWiki content
        $errors = $this->validateDokuWiki($dokuWikiContent);
        if (!empty($errors)) {
            //throw new \Exception("Ошибка валидации сгенерированного DokuWiki: " . implode("; ", $errors));  // @fixme : enable validation
        }

        return $dokuWikiContent;
    }

    /**
     * @param DomElement $dom
     * @param string $className
     *
     * @return \DOMNodeList|false|mixed
     */
    private function findElementsByClass($dom, $className)
    {
        $xpath = new \DOMXPath($dom);
        $query = "//*[contains(concat(' ', normalize-space(@class), ' '), ' $className ')]";
        return $xpath->query($query);
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
            case 'h1':
                return $this->convertHeading($element, '=');
            case 'h2':
                return $this->convertHeading($element, '==');
            case 'h3':
                return $this->convertHeading($element, '===');
            case 'h4':
                return $this->convertHeading($element, '====');
            case 'h5':
                return $this->convertHeading($element, '=====');
            case 'h6':
                return $this->convertHeading($element, '======');
            case 'p':
                return $this->convertParagraph($element);
            case 'br':
                return "\\\\\n";
            case 'b':
            case 'strong':
                return $this->convertBold($element);
            case 'i':
            case 'em':
                return $this->convertItalic($element);
            case 'u':
                return $this->convertUnderline($element);
            case 'a':
                return $this->convertLink($element);
            case 'img':
                return $this->convertImage($element);
            case 'ul':
                return $this->convertList($element, 'ul');
            case 'ol':
                return $this->convertList($element, 'ol');
            case 'li':
                return $this->convertListItem($element);
            case 'table':
                return $this->convertTable($element);
            case 'tr':
                return $this->convertTableRow($element);
            case 'td':
            case 'th':
                return $this->convertTableCell($element);
            case 'pre':
                return $this->convertPreformatted($element);
            case 'code':
                return $this->convertCode($element);
            default:
                return $this->convertGeneric($element);
        }
    }

    private function convertText($text)
    {
        return htmlspecialchars($text->wholeText);
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

    /**
     * @param \DOMElement $element
     *
     * @return string
     */
    private function convertLink($element)
    {
        $href = $element->getAttribute('href');
        $text = $element->textContent;// $this->convertNode($element);

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
        return '<code>' . htmlspecialchars($element->textContent) . '</code>';
    }

    private function convertCode($element)
    {
        return '`' . htmlspecialchars($element->textContent) . '`';
    }

    private function convertGeneric($element)
    {
        return $this->convertNode($element);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////]
    /// Checks

    /**
     * Delete empty strings
     * @param string $output
     *
     * @return string
     */
    private function cleanOutput(string $output)
    {
        // Remove empty lines and leading/trailing spaces
        $output = preg_replace("/^\s+|\s+$/m", '', $output);

        // remove empty lines
        $output = preg_replace("/\n{2,}/", "\n", $output);

        // add \n after headers:
        $output = preg_replace( '/(=+ .*? =+)/', "$1\n", $output);

        return trim($output);
    }

    /**
     * DokuWiki validation
     * @param string $content
     *
     * @return bool
     */
    private function validateDokuWiki($content)
    {
        $errors = [];

        if (trim($content) == '') {
            $errors[] = "Пустое содержимое файла DokuWiki.";
        }

        // Проверка правильного форматирования заголовков
        if (preg_match_all('/^(=+[^=]+?=+)$/m', $content, $matches)) {
            foreach ($matches[0] as $heading) {
                if (!preg_match('/^=+ .+? =+$/', $heading)) {
                    $errors[] = "Неверный формат заголовка: $heading";
                }
            }
        }

        // Проверка на наличие незакрытых тегов
        if (preg_match('/<[^\/>]*>/', $content)) {
            $errors[] = "Найдены незакрытые теги.";
        }

        // Проверка корректности ссылок
        if (preg_match_all('/\[\[(.*?)\]\]/', $content, $matches)) {
            foreach ($matches[1] as $link) {
                if (!preg_match('/^(.+?\|.+?)$/', $link) && !filter_var($link, FILTER_VALIDATE_URL)) {
                    $errors[] = "Неверный формат ссылки: $link";
                }
            }
        }

        // Проверка корректности изображений
        if (preg_match_all('/\{\{(.*?)\}\}/', $content, $matches)) {
            foreach ($matches[1] as $image) {
                if (!preg_match('/^(.+?\|.*)$/', $image)) {
                    $errors[] = "Неверный формат тега изображения: $image";
                }
            }
        }

        // Проверка на незакрытые или неверно вложенные элементы
        $tags = ['**' => '**', '//' => '//', '__' => '__', '<code>' => '</code>', '`' => '`'];
        foreach ($tags as $open => $close) {
            $openCount = substr_count($content, $open);
            $closeCount = substr_count($content, $close);
            if ($openCount !== $closeCount) {
                $errors[] = "Непарный тег: $open";
            }
        }

        return $errors;
    }
}
