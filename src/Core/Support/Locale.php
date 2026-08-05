<?php

declare(strict_types=1);

namespace SilaSeo\Core\Support;

/**
 * Locale formatting helpers for SEO output.
 */
final class Locale
{
    /**
     * Default territory per language when a locale carries only its primary
     * subtag (e.g. "ar" → "ar_AR", "en" → "en_US").
     *
     * @var array<string,string>
     */
    private const TERRITORIES = [
        'ar' => 'AR',
        'en' => 'US',
        'fr' => 'FR',
        'de' => 'DE',
        'es' => 'ES',
        'tr' => 'TR',
    ];

    /**
     * Normalise a locale to the Open Graph `language_TERRITORY` form
     * (e.g. "ar" → "ar_AR", "en-gb" → "en_GB").
     */
    public static function openGraph(string $locale): string
    {
        $parts = preg_split('/[-_]/', $locale) ?: [$locale];
        $language = strtolower($parts[0]);

        if (isset($parts[1]) && $parts[1] !== '') {
            return $language . '_' . strtoupper($parts[1]);
        }

        $territory = self::TERRITORIES[$language] ?? strtoupper($language);

        return $language . '_' . $territory;
    }
}