<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;

final class LinkChecks implements Evaluator
{
    public function run(AnalysisContext $context): array
    {
        if (! $context->hasBody()) {
            return [CheckResult::make('internal_links', Dimension::Links, Priority::Should, 7, CheckStatus::Skip)];
        }

        $count = $context->stats->content->internalLinkCount();

        return [
            CheckResult::make(
                'internal_links',
                Dimension::Links,
                Priority::Should,
                7,
                $count >= 1 ? CheckStatus::Pass : CheckStatus::Fail,
                ['count' => $count],
            ),
        ];
    }
}