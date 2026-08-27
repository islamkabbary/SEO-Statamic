<?php

declare(strict_types=1);

namespace SilaSeo\Core\Sitemap;

/**
 * A single <url> entry in an XML sitemap, with optional hreflang alternates and
 * image references.
 */
final class SitemapUrl
{
    /**
     * @param array<string,string> $alternates hreflang => absolute URL
     * @param list<string>         $images     absolute image URLs
     */
    public function __construct(
        public readonly string $loc,
        public readonly ?string $lastmod = null,
        public readonly array $alternates = [],
        public readonly array $images = [],
    ) {
    }
}