<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Readability;

use SilaSeo\Core\Analysis\Text\ArabicText;

/**
 * Heuristic Arabic readability. English Flesch does not apply (Arabic has no
 * syllabic unit), so this approximates difficulty from average word length
 * (∝ morphological complexity) and sentence length:
 *
 *   score = 100 - (avgWordLength · wordWeight + avgSentenceLength · sentenceWeight)
 *
 * The weights are tunable and this is an UNVALIDATED estimate — surface it as
 * guidance only and keep its scoring weight low.
 */
final class ArabicReadability implements ReadabilityAnalyzer
{
    public function __construct(
        private readonly float $wordWeight = 4.0,
        private readonly float $sentenceWeight = 1.5,
    ) {
    }

    public function analyze(array $words, int $sentenceCount): ReadabilityResult
    {
        $wordCount = count($words);

        if ($wordCount === 0 || $sentenceCount === 0) {
            return ReadabilityResult::unknown();
        }

        $totalChars = 0;

        foreach ($words as $word) {
            $totalChars += mb_strlen(ArabicText::normalize($word), 'UTF-8');
        }

        $averageWordLength = $totalChars / $wordCount;
        $averageSentenceLength = $wordCount / $sentenceCount;

        $raw = 100 - (($averageWordLength * $this->wordWeight) + ($averageSentenceLength * $this->sentenceWeight));
        $score = (int) round(max(0.0, min(100.0, $raw)));

        return new ReadabilityResult($score, $this->labelKey($score), null, true);
    }

    private function labelKey(int $score): string
    {
        return match (true) {
            $score >= 80 => 'very_easy',
            $score >= 60 => 'easy',
            $score >= 40 => 'standard',
            $score >= 20 => 'difficult',
            default => 'very_difficult',
        };
    }
}