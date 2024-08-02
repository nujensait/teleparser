<?php

/**
 * Extracts the domain from a given URL.
 *
 * @param string $url The URL to extract the domain from.
 * @return string The extracted domain.
 */
function getDomainFromUrl($url)
{
    // Parse the URL and get the host component
    $parsedUrl = parse_url($url, PHP_URL_HOST);

    // Remove 'www.' prefix if present
    $domain = preg_replace('/^www\./', '', $parsedUrl);

    return $domain;
}
