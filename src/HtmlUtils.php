<?php

namespace src;

class HtmlUtils
{
    /**
     * Read domain from URL
     * @param $url
     *
     * @return string|false
     */
    function getDomainFromUrl($url)
    {
        // Добавляем схему, если её нет
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        $urlParts = parse_url($url);

        // Проверяем, есть ли хост в распарсенном URL
        if (isset($urlParts['host'])) {
            // Удаляем 'www.' если оно присутствует
            return ($urlParts['sheme'] ?? 'http') . '://' . preg_replace('/^www\./', '', $urlParts['host']);
        }

        return false; // Возвращаем false, если домен не найден
    }

    /**
     * @param $url
     *
     * @return string|null
     */
    function getFileTypeFromUrl($url)
    {
        // Извлекаем путь из URL
        $path = parse_url($url, PHP_URL_PATH);

        // Получаем расширение файла
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Массив соответствий расширений типам файлов
        $fileTypes = [
            'js'    => ['js'],
            'css'   => ['css'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
        ];

        // Проверяем расширение и возвращаем соответствующий тип
        foreach ($fileTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }

        // Если тип не определен, возвращаем null или можно вернуть 'unknown'
        return null;
    }

    /**
     * Make absolute links in html
     * @param string $html
     * @param string $domain
     *
     * @return string
     */
    public function convertRelativeToAbsoluteLinks(string $html, string $domain): string
    {
        // Убедимся, что домен заканчивается на слеш
        $domain = rtrim($domain, '/') . '/';

        // Заменяем ссылки в href атрибутах
        $html = preg_replace_callback(
            '/\shref=(["\'])(.+?)\1/i',
            function($matches) use ($domain) {
                return ' href=' . $matches[1] . $this->convertUrl($matches[2], $domain) . $matches[1];
            },
            $html
        );

        // Заменяем ссылки в src атрибутах
        $html = preg_replace_callback(
            '/\ssrc=(["\'])(.+?)\1/i',
            function($matches) use ($domain) {
                return ' src=' . $matches[1] . $this->convertUrl($matches[2], $domain) . $matches[1];
            },
            $html
        );

        return $html;
    }

    /**
     * @param string $url
     * @param string $domain
     *
     * @return string
     */
    public function convertUrl(string $url, string $domain): string
    {
        // Проверяем, является ли URL относительным
        if (substr($url, 0, 2) === '//' || preg_match("~^(?:f|ht)tps?://~i", $url)) {
            return $url; // URL уже абсолютный
        }

        if ($url[0] === '/') {
            return $domain . ltrim($url, '/');
        }

        return $domain . $url;
    }

    /**
     * Deletes a directory and all its contents (files and subdirectories).
     *
     * @param string $dir The path to the directory to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
