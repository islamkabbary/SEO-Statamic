<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Analysis;

use Illuminate\Support\Facades\Lang;
use SilaSeo\Core\Analysis\AnalysisReport;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;

/**
 * Turns an {@see AnalysisReport} into a JSON-serialisable array with translated,
 * human-readable check messages for the Control Panel panel.
 */
final class ReportPresenter
{
    /**
     * @return array{score:int,colour:string,dimensions:array<string,int>,results:list<array<string,mixed>>}
     */
    public function present(AnalysisReport $report, string $locale): array
    {
        $results = [];

        foreach ($report->results as $result) {
            if ($result->status === CheckStatus::Skip) {
                continue;
            }

            $results[] = [
                'id' => $result->id,
                'status' => $result->status->value,
                'dimension' => $result->dimension->value,
                'priority' => $result->priority->value,
                'message' => $this->message($result, $locale),
            ];
        }

        return [
            'score' => $report->score,
            'colour' => $report->colour->value,
            'dimensions' => $report->dimensionScores,
            'results' => $this->sort($results),
        ];
    }

    private function message(CheckResult $result, string $locale): string
    {
        $message = Lang::get($result->messageKey, $result->params, $locale);

        if ($message !== $result->messageKey) {
            return $message;
        }

        return Lang::get("silaseo::analysis.fallback.{$result->status->value}", [], $locale);
    }

    /**
     * Show failures first, then warnings, then passes.
     *
     * @param list<array<string,mixed>> $results
     *
     * @return list<array<string,mixed>>
     */
    private function sort(array $results): array
    {
        $order = ['fail' => 0, 'warn' => 1, 'pass' => 2];

        usort($results, static fn (array $a, array $b): int => ($order[$a['status']] ?? 3) <=> ($order[$b['status']] ?? 3));

        return $results;
    }
}