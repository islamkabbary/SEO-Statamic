<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Analysis;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Analysis\Text\KeywordMatcher;

final class KeywordMatcherTest extends TestCase
{
    public function testCountsEnglishOccurrences(): void
    {
        $matcher = new KeywordMatcher(false);

        self::assertSame(2, $matcher->count('Sustainable buildings are great. Sustainable design.', 'sustainable'));
    }

    public function testMatchesEnglishPhrase(): void
    {
        $matcher = new KeywordMatcher(false);

        self::assertSame(1, $matcher->count('We build sustainable buildings here.', 'sustainable buildings'));
        self::assertFalse($matcher->contains('We build sustainable homes here.', 'sustainable buildings'));
    }

    public function testEnglishDensity(): void
    {
        $matcher = new KeywordMatcher(false);

        self::assertSame(20.0, $matcher->density('one two three four sustainable', 'sustainable'));
    }

    public function testMatchesArabicAcrossDefiniteArticleAndVariants(): void
    {
        $matcher = new KeywordMatcher(true);

        // المدرسة (with article) and مدرسة (vocalised variant) both match مدرسة.
        self::assertSame(2, $matcher->count('زيارة المدرسة اليوم في مدرسة', 'مدرسة'));
        self::assertTrue($matcher->contains('زرت مدرسه قديمة', 'مدرسة'));
    }
}