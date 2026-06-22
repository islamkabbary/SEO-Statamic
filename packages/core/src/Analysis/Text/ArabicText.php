<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Text;

/**
 * Arabic text normalization for analysis and keyword matching.
 *
 * Base normalize() (always applied): strip bidi control marks, normalize
 * Arabic-Indic digits to ASCII, strip harakat (diacritics), remove tatweel,
 * and unify alef variants (أإآ → ا). This is safe for char-length/readability.
 *
 * matchOnly adds equivalence folding used ONLY for keyword comparison:
 * hamza carriers (ؤ→و, ئ→ي, ء→''), taa marbuta (ة→ه), alef maqsura (ى→ي),
 * and stripping a leading definite article "ال".
 */
final class ArabicText
{
    private const HARAKAT = '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06DC}\x{06DF}-\x{06E8}\x{06EA}-\x{06ED}]/u';
    private const TATWEEL = '/\x{0640}/u';
    private const BIDI = '/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u';

    public static function normalize(string $text, bool $matchOnly = false): string
    {
        $text = (string) preg_replace(self::BIDI, '', $text);
        $text = self::normalizeDigits($text);
        $text = (string) preg_replace(self::HARAKAT, '', $text);
        $text = (string) preg_replace(self::TATWEEL, '', $text);
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا']);

        if (! $matchOnly) {
            return $text;
        }

        $text = strtr($text, ['ؤ' => 'و', 'ئ' => 'ي', 'ء' => '', 'ة' => 'ه', 'ى' => 'ي']);

        return self::stripLeadingArticle($text);
    }

    public static function normalizeDigits(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    private static function stripLeadingArticle(string $word): string
    {
        if (mb_strlen($word, 'UTF-8') > 4 && str_starts_with($word, 'ال')) {
            return mb_substr($word, 2, null, 'UTF-8');
        }

        return $word;
    }
}