<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Tests;

use SilaSeo\Laravel\Facades\Seo;
use SilaSeo\Laravel\MetaService;

final class MetaServiceTest extends TestCase
{
    public function testRendersTitleOgAndAutoOrganizationSchema(): void
    {
        $head = Seo::forUrl('https://example.com/about')
            ->forLocale('en')
            ->title('About us')
            ->description('Who we are')
            ->head();

        self::assertStringContainsString('<title>About us - Sila</title>', $head);
        self::assertStringContainsString('<meta property="og:title" content="About us">', $head);
        self::assertStringContainsString('<link rel="canonical" href="https://example.com/about">', $head);
        self::assertStringContainsString('"@type":"Organization"', $head);
        // JSON-LD escapes slashes so a value can never break out of <script>.
        self::assertStringContainsString('"@id":"https:\/\/example.com\/#organization"', $head);
    }

    public function testOverridesBeatConfigDefaults(): void
    {
        $context = app(MetaService::class)
            ->forUrl('https://example.com')
            ->forLocale('en')
            ->title('Page')
            ->resolveContext();

        self::assertSame('Page', $context->title);
        self::assertSame('Sila', $context->siteName);
    }

    public function testNoindexSetsXRobotsHeader(): void
    {
        $headers = Seo::forUrl('https://example.com/secret')
            ->forLocale('en')
            ->noindex()
            ->httpHeaders();

        self::assertSame('noindex, follow', $headers['X-Robots-Tag']);
    }

    public function testForArrayPayloadFeedsTheCascade(): void
    {
        $head = Seo::forUrl('https://example.com/p')
            ->forLocale('en')
            ->for(['title' => 'From payload', 'description' => 'Desc'])
            ->head();

        self::assertStringContainsString('<title>From payload - Sila</title>', $head);
        self::assertStringContainsString('<meta name="description" content="Desc">', $head);
    }

    public function testArabicRendersRtlLocale(): void
    {
        $head = Seo::forUrl('https://example.com/ar/about')
            ->forLocale('ar')
            ->title('من نحن')
            ->alternate('ar', 'https://example.com/ar/about')
            ->alternate('en', 'https://example.com/en/about')
            ->head();

        self::assertStringContainsString('<meta property="og:locale" content="ar_AR">', $head);
        self::assertStringContainsString('hreflang="x-default"', $head);
        self::assertStringContainsString('من نحن', $head);
    }
}