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

    /**
     * Normalise a value to a BCP 47 language tag suitable for an `hreflang`
     * attribute, or null when it is not a language tag at all.
     *
     * hreflang subtags are hyphen-separated, so `en_US` must become `en-US`;
     * {@see openGraph()} normalises in the opposite direction and must never be
     * used here. Google silently ignores an invalid value, which makes a wrong
     * tag indistinguishable from no tag at all.
     *
     * Returning null matters as much as normalising. Statamic's `Entry::locale()`
     * yields a SITE HANDLE, not a locale, and a single-site install's handle is
     * literally `default` — feeding that straight into the attribute produced
     * `hreflang="default"`. Anything whose primary subtag is not a plausible
     * ISO 639 code is rejected rather than emitted.
     */
    public static function hreflang(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // The wildcard is a valid hreflang value but not a language tag.
        if (strcasecmp($value, 'x-default') === 0) {
            return 'x-default';
        }

        $parts = array_values(array_filter(preg_split('/[-_]/', $value) ?: [], static fn (string $p): bool => $p !== ''));

        if ($parts === []) {
            return null;
        }

        $language = strtolower($parts[0]);

        // ISO 639-1/-2/-3 primary subtags are 2 or 3 alphabetic characters. This
        // is what rejects "default", and any other handle masquerading as a locale.
        if (preg_match('/^[a-z]{2,3}$/', $language) !== 1) {
            return null;
        }

        $tag = [$language];

        foreach (array_slice($parts, 1) as $subtag) {
            if (preg_match('/^[a-z]{4}$/i', $subtag) === 1) {
                // ISO 15924 script: Titlecase, e.g. "Latn".
                $tag[] = ucfirst(strtolower($subtag));
            } elseif (preg_match('/^([a-z]{2}|\d{3})$/i', $subtag) === 1) {
                // ISO 3166-1 region: uppercase alpha-2, or a UN M.49 numeric code.
                $tag[] = strtoupper($subtag);
            } else {
                // Variants and anything unrecognised pass through lowercased.
                $tag[] = strtolower($subtag);
            }
        }

        return implode('-', $tag);
    }
}