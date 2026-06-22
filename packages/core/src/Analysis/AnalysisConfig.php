<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * Tunable thresholds and behaviour for the analysis engine. All numbers live
 * here as data so a project can adjust them without touching check code.
 */
final class AnalysisConfig
{
    /**
     * @param array<string,int>  $minWordsByPageType Minimum body words per page type for content_length.
     * @param list<string>       $disabledChecks     Check ids to skip entirely.
     */
    public function __construct(
        public readonly int $titleMin = 50,
        public readonly int $titleMax = 60,
        public readonly int $titleHardMin = 20,
        public readonly int $titleHardMax = 75,
        public readonly int $descriptionMin = 120,
        public readonly int $descriptionMax = 158,
        public readonly int $descriptionHardMin = 50,
        public readonly int $descriptionHardMax = 180,
        public readonly int $contentPresenceMinWords = 50,
        public readonly int $contentThinWords = 150,
        public readonly float $densityMin = 0.5,
        public readonly float $densityIdealMax = 3.0,
        public readonly float $densityWarnMax = 4.5,
        public readonly float $arabicWordWeight = 4.0,
        public readonly float $arabicSentenceWeight = 1.5,
        public readonly bool $mustGate = true,
        public readonly array $minWordsByPageType = [
            'article' => 300,
            'blog' => 300,
            'product' => 200,
            'landing' => 150,
            'pillar' => 2500,
        ],
        public readonly array $disabledChecks = [],
    ) {
    }

    public static function default(): self
    {
        return new self();
    }

    public function minWordsFor(string $pageType): int
    {
        return $this->minWordsByPageType[$pageType] ?? ($this->minWordsByPageType['article'] ?? 300);
    }

    public function isDisabled(string $checkId): bool
    {
        return in_array($checkId, $this->disabledChecks, true);
    }
}