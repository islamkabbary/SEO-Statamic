<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Readability;

/**
 * Flesch Reading Ease (206.835 - 1.015·ASL - 84.6·ASW) plus Flesch-Kincaid
 * grade level, with a heuristic English syllable counter.
 */
final class EnglishReadability implements ReadabilityAnalyzer
{
    public function analyze(array $words, int $sentenceCount): ReadabilityResult
    {
        $wordCount = count($words);

        if ($wordCount === 0 || $sentenceCount === 0) {
            return ReadabilityResult::unknown();
        }

        $syllables = 0;

        foreach ($words as $word) {
            $syllables += $this->syllables($word);
        }

        $averageSentenceLength = $wordCount / $sentenceCount;
        $averageSyllablesPerWord = $syllables / $wordCount;

        $ease = 206.835 - (1.015 * $averageSentenceLength) - (84.6 * $averageSyllablesPerWord);
        $grade = (0.39 * $averageSentenceLength) + (11.8 * $averageSyllablesPerWord) - 15.59;

        $score = (int) round(max(0.0, min(100.0, $ease)));

        return new ReadabilityResult($score, $this->labelKey($score), round($grade, 1));
    }

    /**
     * Heuristic syllable count: vowel groups, silent-e, and a trailing "le".
     */
    public function syllables(string $word): int
    {
        $word = strtolower((string) preg_replace('/[^a-z]/i', '', $word));

        if ($word === '') {
            return 0;
        }

        if (strlen($word) <= 3) {
            return 1;
        }

        $count = 0;
        $previousWasVowel = false;

        foreach (str_split($word) as $char) {
            $isVowel = str_contains('aeiouy', $char);

            if ($isVowel && ! $previousWasVowel) {
                $count++;
            }

            $previousWasVowel = $isVowel;
        }

        // Drop the silent final "e" (covers plain "-e" and "-le" alike)...
        if (str_ends_with($word, 'e')) {
            $count--;
        }

        // ...then add the syllable back for a consonant + "le" ending (ta-ble).
        if (str_ends_with($word, 'le') && ! str_contains('aeiouy', $word[strlen($word) - 3])) {
            $count++;
        }

        return max(1, $count);
    }

    private function labelKey(int $score): string
    {
        return match (true) {
            $score >= 90 => 'very_easy',
            $score >= 80 => 'easy',
            $score >= 70 => 'fairly_easy',
            $score >= 60 => 'standard',
            $score >= 50 => 'fairly_difficult',
            $score >= 30 => 'difficult',
            default => 'very_difficult',
        };
    }
}