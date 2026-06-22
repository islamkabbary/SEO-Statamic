<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Context;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Context\SeoContextBuilder;
use SilaSeo\Core\Support\TextDirection;

final class SeoContextBuilderTest extends TestCase
{
    public function testDerivesRtlDirectionForArabic(): void
    {
        $context = SeoContextBuilder::for('https://example.com/ar', 'ar')->build();

        self::assertSame(TextDirection::Rtl, $context->direction);
    }

    public function testTrimsValuesAndDropsBlanks(): void
    {
        $context = SeoContextBuilder::for('https://example.com', 'en')
            ->title('  Hello  ')
            ->description('   ')
            ->build();

        self::assertSame('Hello', $context->title);
        self::assertNull($context->description);
    }

    public function testApplyPayloadMapsKnownKeysAndCarriesCustom(): void
    {
        $context = SeoContextBuilder::for('https://example.com', 'en')
            ->applyPayload([
                'title' => 'Mapped title',
                'robots' => 'noindex, follow',
                'schema' => [['@type' => 'Thing']],
                'focus_keyword' => 'laravel seo',
            ])
            ->build();

        self::assertSame('Mapped title', $context->title);
        self::assertSame(['noindex', 'follow'], $context->robots);
        self::assertCount(1, $context->schema);
        self::assertSame('laravel seo', $context->custom['focus_keyword']);
    }

    public function testRobotsAreNormalisedAndDeduplicated(): void
    {
        $context = SeoContextBuilder::for('https://example.com', 'en')
            ->robots(['NoIndex', 'noindex', ' follow '])
            ->build();

        self::assertSame(['noindex', 'follow'], $context->robots);
    }
}