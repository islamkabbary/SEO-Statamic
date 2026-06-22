<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * Read-only bundle handed to every check evaluator: the original input plus the
 * one precomputed {@see TextStatistics}.
 */
final class AnalysisContext
{
    public function __construct(
        public readonly AnalysisInput $input,
        public readonly TextStatistics $stats,
        public readonly AnalysisConfig $config,
    ) {
    }

    public function keyword(): string
    {
        return trim($this->input->focusKeyword);
    }

    public function hasKeyword(): bool
    {
        return $this->keyword() !== '';
    }

    public function hasBody(): bool
    {
        return $this->input->hasBody();
    }
}