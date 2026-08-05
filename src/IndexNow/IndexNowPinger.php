<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\IndexNow;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Submits changed URLs to the IndexNow API (Bing, Yandex, et al.). Best-effort:
 * a network failure never bubbles up into an entry save.
 */
final class IndexNowPinger
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    /**
     * @param list<string> $urls
     */
    public function submit(array $urls, string $key, string $host, string $keyLocation): bool
    {
        $urls = array_values(array_filter($urls, static fn (string $url): bool => $url !== ''));

        if ($urls === [] || $key === '' || $host === '') {
            return false;
        }

        try {
            return Http::timeout(5)->acceptJson()->post(self::ENDPOINT, [
                'host' => $host,
                'key' => $key,
                'keyLocation' => $keyLocation,
                'urlList' => $urls,
            ])->successful();
        } catch (Throwable) {
            return false;
        }
    }
}