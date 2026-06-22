<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Analysis;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Analysis\AnalysisConfig;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;
use SilaSeo\Core\Analysis\ScoreColour;
use SilaSeo\Core\Analysis\Scorer;

final class ScorerTest extends TestCase
{
    public function testWarnGivesHalfCreditAndSkipIsExcluded(): void
    {
        $report = (new Scorer())->score([
            CheckResult::make('a', Dimension::Title, Priority::Should, 10, CheckStatus::Pass),
            CheckResult::make('b', Dimension::Title, Priority::Should, 10, CheckStatus::Warn),
            CheckResult::make('c', Dimension::Images, Priority::Should, 10, CheckStatus::Skip),
        ], AnalysisConfig::default());

        // (10*1 + 10*0.5) / (10+10) = 75; skip excluded entirely.
        self::assertSame(75, $report->score);
    }

    public function testFailingMustCapsColourAtOrangeEvenWhenScoreIsHigh(): void
    {
        $report = (new Scorer())->score([
            CheckResult::make('big', Dimension::Content, Priority::Should, 90, CheckStatus::Pass),
            CheckResult::make('must', Dimension::Title, Priority::Must, 10, CheckStatus::Fail),
        ], AnalysisConfig::default());

        self::assertSame(90, $report->score);
        self::assertSame(ScoreColour::Orange, $report->colour);
    }

    public function testGreenWhenNoMustFails(): void
    {
        $report = (new Scorer())->score([
            CheckResult::make('a', Dimension::Title, Priority::Should, 90, CheckStatus::Pass),
            CheckResult::make('b', Dimension::Title, Priority::Should, 10, CheckStatus::Warn),
        ], AnalysisConfig::default());

        self::assertSame(95, $report->score);
        self::assertSame(ScoreColour::Green, $report->colour);
    }

    public function testAllSkippedScoresZero(): void
    {
        $report = (new Scorer())->score([
            CheckResult::make('a', Dimension::Images, Priority::Should, 10, CheckStatus::Skip),
        ], AnalysisConfig::default());

        self::assertSame(0, $report->score);
    }
}