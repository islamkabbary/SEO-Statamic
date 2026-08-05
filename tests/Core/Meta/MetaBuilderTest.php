<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Meta;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Context\SeoContextBuilder;
use SilaSeo\Core\Meta\MetaBuilder;
use SilaSeo\Core\Meta\MetaTag;

final class MetaBuilderTest extends TestCase
{
    private MetaBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MetaBuilder();
    }

    public function testBuildsTitleWithSiteNameFallbackPattern(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com', 'en')
                ->title('About us')
                ->siteName('Sila')
                ->build(),
        );

        self::assertStringContainsString('<title>About us - Sila</title>', $html);
    }

    public function testHonoursCustomTitlePattern(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com', 'en')
                ->title('About us')
                ->siteName('Sila')
                ->titlePattern('%sitename% %sep% %title%')
                ->build(),
        );

        self::assertStringContainsString('<title>Sila - About us</title>', $html);
    }

    public function testFallsBackToSelfReferentialCanonical(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com/pricing', 'en')->build(),
        );

        self::assertStringContainsString('<link rel="canonical" href="https://example.com/pricing">', $html);
    }

    public function testEmitsRobotsWhenPresent(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com', 'en')->robots(['noindex', 'follow'])->build(),
        );

        self::assertStringContainsString('<meta name="robots" content="noindex, follow">', $html);
    }

    public function testEmitsOpenGraphAndTwitterTags(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com/post', 'en')
                ->title('My post')
                ->description('A summary')
                ->image('https://example.com/og.jpg')
                ->siteName('Sila')
                ->ogType('article')
                ->build(),
        );

        self::assertStringContainsString('<meta property="og:type" content="article">', $html);
        self::assertStringContainsString('<meta property="og:title" content="My post">', $html);
        self::assertStringContainsString('<meta property="og:description" content="A summary">', $html);
        self::assertStringContainsString('<meta property="og:image" content="https://example.com/og.jpg">', $html);
        self::assertStringContainsString('<meta property="og:locale" content="en_US">', $html);
        self::assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
        self::assertStringContainsString('<meta name="twitter:title" content="My post">', $html);
    }

    public function testArabicEmitsRtlLocaleAndAlternates(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com/ar/post', 'ar')
                ->title('مقالتي')
                ->siteName('سيلا')
                ->alternate('ar', 'https://example.com/ar/post')
                ->alternate('en', 'https://example.com/en/post')
                ->build(),
        );

        self::assertStringContainsString('<title>مقالتي - سيلا</title>', $html);
        self::assertStringContainsString('<meta property="og:locale" content="ar_AR">', $html);
        self::assertStringContainsString('<meta property="og:locale:alternate" content="en_US">', $html);
        self::assertStringContainsString('<link rel="alternate" href="https://example.com/en/post" hreflang="en">', $html);
        self::assertStringContainsString('hreflang="x-default"', $html);
    }

    public function testEscapesAttributeValues(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com', 'en')
                ->description('Quote "test" & <tag>')
                ->build(),
        );

        self::assertStringContainsString('content="Quote &quot;test&quot; &amp; &lt;tag&gt;"', $html);
    }

    public function testEmitsCustomMetaTags(): void
    {
        $html = $this->render(
            SeoContextBuilder::for('https://example.com', 'en')
                ->metaTag('google-site-verification', 'abc123')
                ->build(),
        );

        self::assertStringContainsString('<meta name="google-site-verification" content="abc123">', $html);
    }

    private function render(\SilaSeo\Core\Context\SeoContext $context): string
    {
        return implode("\n", array_map(
            static fn (MetaTag $tag): string => $tag->render(),
            $this->builder->build($context),
        ));
    }
}