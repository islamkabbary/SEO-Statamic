<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;

final class TitleChecks implements Evaluator
{
    public function run(AnalysisContext $context): array
    {
        $title = trim($context->input->title);
        $length = mb_strlen($title, 'UTF-8');

        return [
            $this->presence($title, $length),
            $this->length($title, $length, $context),
        ];
    }

    private function presence(string $title, int $length): CheckResult
    {
        $status = match (true) {
            $title === '' => CheckStatus::Fail,
            $length < 20 || $length > 100 => CheckStatus::Warn,
            default => CheckStatus::Pass,
        };

        return CheckResult::make('title_presence', Dimension::Title, Priority::Must, 15, $status, ['length' => $length]);
    }

    private function length(string $title, int $length, AnalysisContext $context): CheckResult
    {
        if ($title === '') {
            return CheckResult::make('title_length', Dimension::Title, Priority::Should, 12, CheckStatus::Skip);
        }

        $config = $context->config;

        $status = match (true) {
            $length >= $config->titleMin && $length <= $config->titleMax => CheckStatus::Pass,
            $length < $config->titleHardMin || $length > $config->titleHardMax => CheckStatus::Fail,
            default => CheckStatus::Warn,
        };

        return CheckResult::make('title_length', Dimension::Title, Priority::Should, 12, $status, ['length' => $length]);
    }
}