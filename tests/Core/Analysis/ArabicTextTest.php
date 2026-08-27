<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Analysis;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Analysis\Text\ArabicText;

final class ArabicTextTest extends TestCase
{
    public function testStripsHarakat(): void
    {
        self::assertSame('محمد', ArabicText::normalize('مُحَمَّد'));
    }

    public function testUnifiesAlefVariants(): void
    {
        self::assertSame('احمد', ArabicText::normalize('أحمد'));
        self::assertSame('ايمان', ArabicText::normalize('إيمان'));
        self::assertSame('امن', ArabicText::normalize('آمن'));
    }

    public function testRemovesTatweel(): void
    {
        self::assertSame('محمد', ArabicText::normalize('محـــمد'));
    }

    public function testNormalizesArabicIndicDigits(): void
    {
        self::assertSame('2024', ArabicText::normalize('٢٠٢٤'));
    }

    public function testStripsBidiMarks(): void
    {
        self::assertSame('محمد', ArabicText::normalize("\u{200F}محمد\u{200E}"));
    }

    public function testMatchOnlyFoldsTaaMarbutaAndStripsArticle(): void
    {
        self::assertSame('مدرسه', ArabicText::normalize('المدرسة', true));
        self::assertSame('مدرسه', ArabicText::normalize('مدرسة', true));
    }

    public function testBaseNormalizeKeepsTaaMarbuta(): void
    {
        self::assertSame('مدرسة', ArabicText::normalize('مدرسة'));
    }
}