<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * The result of analysing a page: an overall 0-100 score + colour, the
 * per-dimension subscores, and every individual {@see CheckResult}.
 */
final class AnalysisReport
{
    /**
     * @param list<CheckResult>            $results
     * @param array<string,int>           $dimensionScores Dimension value => 0-100 subscore.
     */
    public function __construct(
        public readonly int $score,
        public readonly ScoreColour $colour,
        public readonly array $dimensionScores,
        public readonly array $results,
    ) {
    }

    /**
     * @return list<CheckResult>
     */
    public function failing(): array
    {
        return array_values(array_filter(
            $this->results,
            static fn (CheckResult $r): bool => $r->status === CheckStatus::Fail,
        ));
    }

    /**
     * @return list<CheckResult>
     */
    public function problems(): array
    {
        return array_values(array_filter(
            $this->results,
            static fn (CheckResult $r): bool => $r->status === CheckStatus::Fail || $r->status === CheckStatus::Warn,
        ));
    }
}