<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Text;

/**
 * Locale-agnostic word and sentence tokenization for plain text. Word tokens
 * must contain at least one letter (pure-number tokens are dropped); sentence
 * terminators are . ! ? and the Arabic ؟ (Arabic ، ؛ are NOT terminators).
 */
final class Tokenizer
{
    private const WORD = '/\p{L}[\p{L}\p{M}\p{N}\'\x{2019}]*/u';
    private const SENTENCE_SPLIT = '/[.!?؟]+/u';

    /**
     * @return list<string>
     */
    public static function words(string $text): array
    {
        preg_match_all(self::WORD, $text, $matches);

        return $matches[0];
    }

    /**
     * @return list<string>
     */
    public static function sentences(string $text): array
    {
        // Protect decimal points (3.14) and thousands so they don't split sentences.
        $text = (string) preg_replace('/(\d)\.(\d)/u', '$1$2', $text);

        $parts = preg_split(self::SENTENCE_SPLIT, $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            array_map('trim', $parts),
            static fn (string $s): bool => preg_match('/\p{L}/u', $s) === 1,
        ));
    }

    public static function normalizeWord(string $word, bool $isArabic): string
    {
        $word = mb_strtolower($word, 'UTF-8');

        return $isArabic ? ArabicText::normalize($word, true) : $word;
    }
}