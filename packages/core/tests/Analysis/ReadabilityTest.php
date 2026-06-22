<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Analysis;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Analysis\Readability\ArabicReadability;
use SilaSeo\Core\Analysis\Readability\EnglishReadability;
use SilaSeo\Core\Analysis\Text\Tokenizer;

final class ReadabilityTest extends TestCase
{
    public function testEnglishSyllableHeuristic(): void
    {
        $en = new EnglishReadability();

        self::assertSame(1, $en->syllables('cat'));
        self::assertSame(2, $en->syllables('table'));
        self::assertSame(2, $en->syllables('reading'));
        self::assertSame(3, $en->syllables('beautiful'));
    }

    public function testSimpleEnglishSentenceScoresVeryEasy(): void
    {
        $en = new EnglishReadability();
        $result = $en->analyze(Tokenizer::words('The cat sat on the mat.'), 1);

        self::assertSame(100, $result->score);
        self::assertSame('very_easy', $result->labelKey);
    }

    public function testUnknownWhenEmpty(): void
    {
        $result = (new EnglishReadability())->analyze([], 0);

        self::assertSame('unknown', $result->labelKey);
        self::assertSame(0, $result->score);
    }

    public function testArabicReadabilityProducesHeuristicScore(): void
    {
        $ar = new ArabicReadability();
        $result = $ar->analyze(Tokenizer::words('القطة جلست على الحصيرة الصغيرة'), 1);

        self::assertTrue($result->heuristic);
        self::assertNotSame('unknown', $result->labelKey);
        self::assertGreaterThanOrEqual(0, $result->score);
        self::assertLessThanOrEqual(100, $result->score);
    }
}