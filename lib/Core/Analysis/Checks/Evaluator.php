<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;

/**
 * Produces one or more {@see \SilaSeo\Core\Analysis\CheckResult}s for a
 * dimension. Evaluators are stateless and read only from the context.
 */
interface Evaluator
{
    /**
     * @return list<\SilaSeo\Core\Analysis\CheckResult>
     */
    public function run(AnalysisContext $context): array;
}