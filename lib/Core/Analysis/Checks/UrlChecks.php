<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;

final class UrlChecks implements Evaluator
{
    public function run(AnalysisContext $context): array
    {
        $slug = trim($context->input->slug);

        $status = match (true) {
            $slug === '' => CheckStatus::Fail,
            str_contains($slug, '_') || str_contains($slug, '--') => CheckStatus::Warn,
            default => CheckStatus::Pass,
        };

        return [
            CheckResult::make('url_presence', Dimension::Links, Priority::Must, 5, $status),
        ];
    }
}