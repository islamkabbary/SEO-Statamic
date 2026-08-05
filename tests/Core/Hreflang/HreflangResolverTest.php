<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Hreflang;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Hreflang\Alternate;
use SilaSeo\Core\Hreflang\HreflangResolver;

final class HreflangResolverTest extends TestCase
{
    public function testReturnsNothingWithoutAlternates(): void
    {
        self::assertSame([], (new HreflangResolver())->resolve([]));
    }

    public function testSynthesisesXDefaultFromFirstAlternate(): void
    {
        $tags = (new HreflangResolver())->resolve([
            new Alternate('ar', 'https://example.com/ar'),
            new Alternate('en', 'https://example.com/en'),
        ]);

        $rendered = array_map(static fn ($tag) => $tag->render(), $tags);

        self::assertCount(3, $rendered);
        self::assertStringContainsString('hreflang="ar"', $rendered[0]);
        self::assertStringContainsString('hreflang="en"', $rendered[1]);
        self::assertStringContainsString('hreflang="x-default"', $rendered[2]);
        self::assertStringContainsString('href="https://example.com/ar"', $rendered[2]);
    }

    public function testKeepsExplicitXDefault(): void
    {
        $tags = (new HreflangResolver())->resolve([
            new Alternate('ar', 'https://example.com/ar'),
            new Alternate('x-default', 'https://example.com'),
        ]);

        self::assertCount(2, $tags);
    }
}