<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;

final class ContentChecks implements Evaluator
{
    public function run(AnalysisContext $context): array
    {
        return [
            $this->presence($context),
            $this->length($context),
            $this->h1($context),
            $this->readability($context),
        ];
    }

    private function presence(AnalysisContext $context): CheckResult
    {
        $words = $context->stats->wordCount;
        $config = $context->config;

        $status = match (true) {
            $words < $config->contentPresenceMinWords => CheckStatus::Fail,
            $words < $config->contentThinWords => CheckStatus::Warn,
            default => CheckStatus::Pass,
        };

        return CheckResult::make('content_presence', Dimension::Content, Priority::Must, 12, $status, ['words' => $words]);
    }

    private function length(AnalysisContext $context): CheckResult
    {
        if (! $context->hasBody()) {
            return CheckResult::make('content_length', Dimension::Content, Priority::Should, 10, CheckStatus::Skip);
        }

        $words = $context->stats->wordCount;
        $min = $context->config->minWordsFor($context->input->pageType);

        $status = match (true) {
            $words >= $min => CheckStatus::Pass,
            $words >= $context->config->contentThinWords => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        return CheckResult::make('content_length', Dimension::Content, Priority::Should, 10, $status, [
            'words' => $words,
            'min' => $min,
        ]);
    }

    private function h1(AnalysisContext $context): CheckResult
    {
        if (! $context->hasBody()) {
            return CheckResult::make('h1_presence', Dimension::Content, Priority::Must, 9, CheckStatus::Skip);
        }

        $count = $context->stats->content->h1Count();

        $status = match (true) {
            $count === 1 => CheckStatus::Pass,
            $count === 0 => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        return CheckResult::make('h1_presence', Dimension::Content, Priority::Must, 9, $status, ['count' => $count]);
    }

    private function readability(AnalysisContext $context): CheckResult
    {
        $result = $context->stats->readability;

        if ($result->labelKey === 'unknown') {
            return CheckResult::make('readability', Dimension::Content, Priority::Nice, 5, CheckStatus::Skip);
        }

        $status = match (true) {
            $result->score >= 60 => CheckStatus::Pass,
            $result->score >= 40 => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        return CheckResult::make('readability', Dimension::Content, Priority::Nice, 5, $status, [
            'score' => $result->score,
            'label' => $result->labelKey,
        ]);
    }
}