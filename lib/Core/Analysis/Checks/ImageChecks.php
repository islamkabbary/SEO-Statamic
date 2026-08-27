<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;

final class ImageChecks implements Evaluator
{
    public function run(AnalysisContext $context): array
    {
        $total = $context->stats->content->imageCount();

        if (! $context->hasBody() || $total === 0) {
            return [CheckResult::make('image_alt_text', Dimension::Images, Priority::Should, 8, CheckStatus::Skip)];
        }

        $withAlt = $context->stats->content->imagesWithAltCount();
        $ratio = $withAlt / $total;

        $status = match (true) {
            $ratio >= 1.0 => CheckStatus::Pass,
            $ratio >= 0.8 => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        return [
            CheckResult::make('image_alt_text', Dimension::Images, Priority::Should, 8, $status, [
                'total' => $total,
                'with_alt' => $withAlt,
            ]),
        ];
    }
}