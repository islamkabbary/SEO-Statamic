<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Text;

/**
 * Counts and detects a focus keyword (single or multi-word) inside text, after
 * locale-aware normalization on BOTH sides (so unvocalised Arabic and
 * case/diacritic variants still match).
 */
final class KeywordMatcher
{
    public function __construct(private readonly bool $isArabic)
    {
    }

    /**
     * Number of (possibly multi-word) keyword occurrences in the text.
     */
    public function count(string $text, string $keyword): int
    {
        return $this->countIn($this->normalizedTokens($text), $keyword);
    }

    /**
     * Count keyword occurrences against an already-normalized token list, so a
     * shared haystack can be matched against many keywords without re-tokenizing.
     *
     * @param list<string> $haystack Output of {@see normalizedTokens()}.
     */
    public function countIn(array $haystack, string $keyword): int
    {
        $needle = $this->normalizeTokens(Tokenizer::words($keyword));

        if ($needle === []) {
            return 0;
        }

        $needleLength = count($needle);
        $occurrences = 0;

        for ($i = 0, $max = count($haystack) - $needleLength; $i <= $max; $i++) {
            if (array_slice($haystack, $i, $needleLength) === $needle) {
                $occurrences++;
            }
        }

        return $occurrences;
    }

    /**
     * Tokenize and normalize text once for reuse across many keyword matches.
     *
     * @return list<string>
     */
    public function normalizedTokens(string $text): array
    {
        return $this->normalizeTokens(Tokenizer::words($text));
    }

    public function contains(string $text, string $keyword): bool
    {
        return $this->count($text, $keyword) > 0;
    }

    /**
     * Keyword density as a percentage of total words.
     */
    public function density(string $text, string $keyword): float
    {
        $totalWords = count(Tokenizer::words($text));

        if ($totalWords === 0) {
            return 0.0;
        }

        return ($this->count($text, $keyword) / $totalWords) * 100;
    }

    /**
     * @param list<string> $words
     *
     * @return list<string>
     */
    private function normalizeTokens(array $words): array
    {
        return array_values(array_filter(
            array_map(fn (string $w): string => Tokenizer::normalizeWord($w, $this->isArabic), $words),
            static fn (string $w): bool => $w !== '',
        ));
    }
}