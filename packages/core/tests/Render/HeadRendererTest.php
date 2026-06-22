<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Render;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Context\SeoContextBuilder;
use SilaSeo\Core\Render\HeadRenderer;
use SilaSeo\Core\Schema\Types\Article;
use SilaSeo\Core\Schema\Types\Organization;

final class HeadRendererTest extends TestCase
{
    private HeadRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new HeadRenderer();
    }

    public function testRendersMetaWithoutSchema(): void
    {
        $result = $this->renderer->render(
            SeoContextBuilder::for('https://example.com', 'en')->title('Home')->build(),
        );

        self::assertStringContainsString('<title>Home</title>', $result->headHtml);
        self::assertSame('', $result->jsonLd);
        self::assertStringNotContainsString('ld+json', $result->headHtml);
    }

    public function testAppendsJsonLdScriptWhenSchemaPresent(): void
    {
        $context = SeoContextBuilder::for('https://example.com', 'en')
            ->title('Home')
            ->schema((new Organization('Sila', 'https://example.com'))->withId('https://example.com/#org')->toArray())
            ->build();

        $result = $this->renderer->render($context);

        self::assertStringContainsString('<script type="application/ld+json">', $result->headHtml);
        self::assertStringContainsString('"@type":"Organization"', $result->jsonLd);
    }

    public function testSetsXRobotsHeaderWhenNoindex(): void
    {
        $result = $this->renderer->render(
            SeoContextBuilder::for('https://example.com', 'en')->robots(['noindex', 'nofollow'])->build(),
        );

        self::assertSame('noindex, nofollow', $result->httpHeaders['X-Robots-Tag']);
    }

    public function testArabicGoldenOutput(): void
    {
        $context = SeoContextBuilder::for('https://example.com/ar/post', 'ar')
            ->title('مرحبا بالعالم')
            ->description('وصف عربي')
            ->siteName('سيلا')
            ->image('https://example.com/og.jpg')
            ->robots(['index', 'follow'])
            ->alternate('ar', 'https://example.com/ar/post')
            ->alternate('en', 'https://example.com/en/post')
            ->schema((new Article('مرحبا بالعالم'))->description('وصف عربي')->inLanguage('ar')->toArray())
            ->build();

        $result = $this->renderer->render($context);

        self::assertStringContainsString('<title>مرحبا بالعالم - سيلا</title>', $result->headHtml);
        self::assertStringContainsString('<meta property="og:locale" content="ar_AR">', $result->headHtml);
        self::assertStringContainsString('وصف عربي', $result->jsonLd);
        self::assertStringContainsString('"inLanguage":"ar"', $result->jsonLd);
        self::assertSame('index, follow', $result->httpHeaders['X-Robots-Tag']);
    }
}