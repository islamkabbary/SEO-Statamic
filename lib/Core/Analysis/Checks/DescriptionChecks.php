<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;

final class DescriptionChecks implements Evaluator
{
    public function run(AnalysisContext $context): array
    {
        $description = trim($context->input->description);
        $length = mb_strlen($description, 'UTF-8');

        return [
            $this->presence($description, $length),
            $this->length($description, $length, $context),
        ];
    }

    private function presence(string $description, int $length): CheckResult
    {
        $status = match (true) {
            $description === '' => CheckStatus::Fail,
            $length < 40 => CheckStatus::Warn,
            default => CheckStatus::Pass,
        };

        return CheckResult::make('description_presence', Dimension::Description, Priority::Must, 13, $status, ['length' => $length]);
    }

    private function length(string $description, int $length, AnalysisContext $context): CheckResult
    {
        if ($description === '') {
            return CheckResult::make('description_length', Dimension::Description, Priority::Should, 11, CheckStatus::Skip);
        }

        $config = $context->config;

        $status = match (true) {
            $length >= $config->descriptionMin && $length <= $config->descriptionMax => CheckStatus::Pass,
            $length < $config->descriptionHardMin || $length > $config->descriptionHardMax => CheckStatus::Fail,
            default => CheckStatus::Warn,
        };

        return CheckResult::make('description_length', Dimension::Description, Priority::Should, 11, $status, ['length' => $length]);
    }
}