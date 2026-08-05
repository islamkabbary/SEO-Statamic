<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\IndexNow;

/**
 * Pure derivation of the IndexNow submission parameters (host + key file
 * location) from a page URL and key. Returns null when the URL has no host.
 */
final class IndexNowSubmission
{
    /**
     * @return array{host: string, keyLocation: string, url: string}|null
     */
    public static function for(string $url, string $key): ?array
    {
        $url = trim($url);

        if ($url === '' || $key === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $scheme = is_string($scheme) && $scheme !== '' ? $scheme : 'https';

        return [
            'host' => $host,
            'keyLocation' => $scheme . '://' . $host . '/' . $key . '.txt',
            'url' => $url,
        ];
    }
}