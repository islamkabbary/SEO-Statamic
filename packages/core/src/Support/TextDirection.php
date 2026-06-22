<?php

declare(strict_types=1);

namespace SilaSeo\Core\Support;

/**
 * Writing direction of a document, used for the <html dir> attribute and
 * RTL-aware rendering of meta/JSON-LD output.
 */
enum TextDirection: string
{
    case Ltr = 'ltr';
    case Rtl = 'rtl';

    /**
     * Resolve the direction for a locale. Right-to-left languages are matched
     * by their ISO 639 primary subtag (ar, fa, he, ur, ...).
     */
    public static function forLocale(string $locale): self
    {
        $primary = strtolower(explode('-', str_replace('_', '-', $locale))[0]);

        return in_array($primary, self::rtlLanguages(), true) ? self::Rtl : self::Ltr;
    }

    /**
     * @return list<string>
     */
    private static function rtlLanguages(): array
    {
        return ['ar', 'fa', 'he', 'ur', 'ps', 'sd', 'ckb', 'dv', 'yi'];
    }
}