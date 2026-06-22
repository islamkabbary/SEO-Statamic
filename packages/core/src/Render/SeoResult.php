<?php

declare(strict_types=1);

namespace SilaSeo\Core\Render;

/**
 * The rendered SEO output for a single page. Bridges drop {@see $headHtml} into
 * the <head>, may expose {@see $jsonLd} to a headless client, and apply
 * {@see $httpHeaders} (e.g. X-Robots-Tag) at the response layer.
 */
final class SeoResult
{
    /**
     * @param array<string,string> $httpHeaders
     */
    public function __construct(
        public readonly string $headHtml,
        public readonly string $jsonLd,
        public readonly array $httpHeaders = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->headHtml === '' && $this->jsonLd === '';
    }
}