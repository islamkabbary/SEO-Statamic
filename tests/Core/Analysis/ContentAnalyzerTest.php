<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Analysis;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Analysis\AnalysisInput;
use SilaSeo\Core\Analysis\AnalysisReport;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\ContentAnalyzer;
use SilaSeo\Core\Analysis\ScoreColour;

final class ContentAnalyzerTest extends TestCase
{
    private ContentAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new ContentAnalyzer();
    }

    public function testWellOptimisedEnglishPageScoresHighAndNotRed(): void
    {
        $filler = str_repeat('Brewing is a craft that rewards patience and steady care. ', 16);
        $body = '<h1>Coffee Brewing Guide</h1>'
            . '<p>Coffee brewing rewards patience. ' . $filler . ' Read more about coffee here. <a href="/brew">more</a></p>'
            . '<img src="x.jpg" alt="a coffee cup">';

        $report = $this->analyzer->analyze(new AnalysisInput(
            focusKeyword: 'coffee',
            title: 'The Complete Coffee Brewing Guide for New Beginners',
            description: 'Learn the complete art of coffee brewing at home with our simple step by step guide covering beans, grind size, water and timing.',
            slug: 'complete-coffee-brewing-guide',
            body: $body,
            locale: 'en',
            pageType: 'landing',
        ));

        self::assertNotSame(ScoreColour::Red, $report->colour);
        self::assertGreaterThanOrEqual(75, $report->score);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'focus_keyword_presence')->status);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'title_keyword')->status);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'keyword_density')->status);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'internal_links')->status);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'image_alt_text')->status);
    }

    public function testEmptyPageIsRedWithFailingMusts(): void
    {
        $report = $this->analyzer->analyze(new AnalysisInput(
            focusKeyword: '',
            title: '',
            description: '',
            slug: '',
            body: '',
            locale: 'en',
        ));

        self::assertSame(ScoreColour::Red, $report->colour);
        self::assertSame(CheckStatus::Fail, $this->checkResult($report, 'focus_keyword_presence')->status);
        self::assertSame(CheckStatus::Fail, $this->checkResult($report, 'title_presence')->status);
        self::assertSame(CheckStatus::Skip, $this->checkResult($report, 'image_alt_text')->status);
    }

    public function testArabicPageIsAnalysedWithKeywordMatching(): void
    {
        $filler = str_repeat('القهوة مشروب لذيذ يحبه الكثير من الناس حول العالم كله. ', 18);
        $body = '<h1>دليل القهوة</h1><p>القهوة مشروب رائع. ' . $filler . ' <a href="/more">المزيد</a></p>';

        $report = $this->analyzer->analyze(new AnalysisInput(
            focusKeyword: 'القهوة',
            title: 'دليل القهوة الكامل لإعداد أفضل فنجان في المنزل بسهولة',
            description: 'تعرف على طريقة إعداد القهوة في المنزل خطوة بخطوة مع نصائح عن الحبوب ودرجة الطحن والماء ووقت التحضير المثالي لأفضل مذاق ممكن دائما.',
            slug: 'دليل-القهوة',
            body: $body,
            locale: 'ar',
            pageType: 'landing',
        ));

        self::assertGreaterThan(0, $report->score);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'focus_keyword_presence')->status);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'title_keyword')->status);
        self::assertSame(CheckStatus::Pass, $this->checkResult($report, 'url_keyword')->status);
    }

    private function checkResult(AnalysisReport $report, string $id): CheckResult
    {
        foreach ($report->results as $result) {
            if ($result->id === $id) {
                return $result;
            }
        }

        self::fail("Missing check result: {$id}");
    }
}