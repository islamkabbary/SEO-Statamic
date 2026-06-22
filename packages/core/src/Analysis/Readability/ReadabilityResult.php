<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Readability;

/**
 * A readability outcome: a 0-100 ease score, a label key, and (for English) an
 * approximate US grade level. Arabic scores are heuristic estimates, not a
 * validated metric — surface them as guidance only.
 */
final class ReadabilityResult
{
    public function __construct(
        public readonly int $score,
        public readonly string $labelKey,
        public readonly ?float $gradeLevel = null,
        public readonly bool $heuristic = false,
    ) {
    }

    public static function unknown(): self
    {
        return new self(0, 'unknown');
    }
}