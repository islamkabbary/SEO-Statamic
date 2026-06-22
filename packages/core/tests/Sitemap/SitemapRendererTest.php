<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Sitemap;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Sitemap\SitemapRenderer;
use SilaSeo\Core\Sitemap\SitemapUrl;

final class SitemapRendererTest extends TestCase
{
    public function testRendersUrlsWithAlternatesAndImages(): void
    {
        $xml = (new SitemapRenderer())->render([
            new SitemapUrl(
                loc: 'https://example.com/ar/blog/post',
                lastmod: '2026-06-21',
                alternates: ['ar' => 'https://example.com/ar/blog/post', 'en' => 'https://example.com/en/blog/post'],
                images: ['https://example.com/img.jpg'],
            ),
        ]);

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<loc>https://example.com/ar/blog/post</loc>', $xml);
        self::assertStringContainsString('<lastmod>2026-06-21</lastmod>', $xml);
        self::assertStringContainsString('<xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/blog/post"/>', $xml);
        self::assertStringContainsString('<image:image><image:loc>https://example.com/img.jpg</image:loc></image:image>', $xml);
    }

    public function testEscapesAmpersandsInUrls(): void
    {
        $xml = (new SitemapRenderer())->render([
            new SitemapUrl(loc: 'https://example.com/p?a=1&b=2'),
        ]);

        self::assertStringContainsString('https://example.com/p?a=1&amp;b=2', $xml);
        self::assertStringNotContainsString('a=1&b=2', $xml);
    }

    public function testEmptyListProducesValidUrlset(): void
    {
        $xml = (new SitemapRenderer())->render([]);

        self::assertStringContainsString('<urlset', $xml);
        self::assertStringContainsString('</urlset>', $xml);
    }

    public function testDeduplicatesRepeatedLocations(): void
    {
        $xml = (new SitemapRenderer())->render([
            new SitemapUrl(loc: 'https://example.com/ar/page'),
            new SitemapUrl(loc: 'https://example.com/ar/page'),
            new SitemapUrl(loc: 'https://example.com/ar/other'),
        ]);

        self::assertSame(2, substr_count($xml, '<loc>'));
        self::assertSame(1, substr_count($xml, '<loc>https://example.com/ar/page</loc>'));
    }
}