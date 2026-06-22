<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Readability;

interface ReadabilityAnalyzer
{
    /**
     * @param list<string> $words All word tokens of the body, in order.
     */
    public function analyze(array $words, int $sentenceCount): ReadabilityResult;
}