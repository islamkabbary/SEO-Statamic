<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * Rolls individual {@see CheckResult}s into an overall 0-100 score + colour and
 * per-dimension subscores. Skipped checks are excluded from both numerator and
 * denominator; a failing Must check soft-gates the colour to at most Orange.
 */
final class Scorer
{
    /**
     * @param list<CheckResult> $results
     */
    public function score(array $results, AnalysisConfig $config): AnalysisReport
    {
        $overall = $this->weightedScore($results);
        $colour = ScoreColour::fromScore($overall);

        if ($config->mustGate && $this->hasFailingMust($results)) {
            $colour = $colour->capAt(ScoreColour::Orange);
        }

        return new AnalysisReport($overall, $colour, $this->dimensionScores($results), $results);
    }

    /**
     * @param list<CheckResult> $results
     */
    private function weightedScore(array $results): int
    {
        $achieved = 0.0;
        $possible = 0.0;

        foreach ($results as $result) {
            if (! $result->status->countsTowardScore()) {
                continue;
            }

            $achieved += $result->weight * $result->status->weightFactor();
            $possible += $result->weight;
        }

        return $possible > 0.0 ? (int) round(($achieved / $possible) * 100) : 0;
    }

    /**
     * @param list<CheckResult> $results
     *
     * @return array<string,int>
     */
    private function dimensionScores(array $results): array
    {
        $scores = [];

        foreach (Dimension::cases() as $dimension) {
            $subset = array_values(array_filter(
                $results,
                static fn (CheckResult $r): bool => $r->dimension === $dimension && $r->status->countsTowardScore(),
            ));

            if ($subset !== []) {
                $scores[$dimension->value] = $this->weightedScore($subset);
            }
        }

        return $scores;
    }

    /**
     * @param list<CheckResult> $results
     */
    private function hasFailingMust(array $results): bool
    {
        foreach ($results as $result) {
            if ($result->priority === Priority::Must && $result->status === CheckStatus::Fail) {
                return true;
            }
        }

        return false;
    }
}