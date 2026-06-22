<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * Outcome of a single SEO check. Score weight: Pass=1.0, Warn=0.5, Fail=0.0;
 * Skip is excluded from scoring entirely.
 */
enum CheckStatus: string
{
    case Pass = 'pass';
    case Warn = 'warn';
    case Fail = 'fail';
    case Skip = 'skip';

    public function weightFactor(): float
    {
        return match ($this) {
            self::Pass => 1.0,
            self::Warn => 0.5,
            self::Fail => 0.0,
            self::Skip => 0.0,
        };
    }

    public function countsTowardScore(): bool
    {
        return $this !== self::Skip;
    }
}