<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fields;

use SilaSeo\Statamic\Locale\LocaleStrategy;

/**
 * The single path by which the package reads an SEO field off an entry.
 *
 * Every consumer -- the meta mapper, the content analyser, the internal-link
 * corpus, the sitemap -- goes through here. Before, each hardcoded its own handle
 * list, so adopting a project whose fields are named differently meant finding and
 * editing four separate call sites, and missing one produced a field that silently
 * read as empty rather than failing.
 *
 * Resolution order for a logical key, given a locale:
 *
 *   1. every mapped handle with the locale's suffix   (seo_title_ar, title_ar)
 *   2. every mapped handle bare                       (seo_title,    title)
 *   3. the profile's configured default
 *   4. null
 *
 * All locale-specific handles are tried before any bare one. The other order
 * serves an English `seo_title` to an Arabic visitor whose entry has `title_ar`.
 *
 * An empty value never stops the walk. A blank `seo_title_ar` means "not
 * translated", not "the title is blank" -- treating it as an answer is what leaves
 * pages with an empty <title>.
 */
final class FieldResolver
{
    public function __construct(
        private readonly FieldMap $map,
        private readonly ValueReader $reader,
        private readonly LocaleStrategy $locale,
    ) {
    }

    public function map(): FieldMap
    {
        return $this->map;
    }

    public function localeStrategy(): LocaleStrategy
    {
        return $this->locale;
    }

    /**
     * The first non-empty raw value for a logical key.
     */
    public function raw(object $entry, string $key, ?string $locale = null): mixed
    {
        foreach ($this->map->candidates($key, $locale ?? $this->locale->current()) as $handle) {
            $value = $this->reader->read($entry, $handle);

            if (self::isFilled($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Which concrete handle actually answered. Diagnostics only -- the doctor
     * command reports it so a misconfigured map is visible rather than silent.
     */
    public function handleUsed(object $entry, string $key, ?string $locale = null): ?string
    {
        foreach ($this->map->candidates($key, $locale ?? $this->locale->current()) as $handle) {
            if (self::isFilled($this->reader->read($entry, $handle))) {
                return $handle;
            }
        }

        return null;
    }

    public function string(object $entry, string $key, ?string $locale = null): ?string
    {
        $value = $this->raw($entry, $key, $locale);

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed !== '') {
                return $trimmed;
            }
        } elseif (is_scalar($value)) {
            return (string) $value;
        }

        $default = $this->map->defaultFor($key);

        return $default !== null && trim($default) !== '' ? trim($default) : null;
    }

    /**
     * A toggle. Absent and false are the same thing for every flag the package
     * reads, so an unmapped key is simply false rather than an error.
     */
    public function bool(object $entry, string $key, ?string $locale = null): bool
    {
        if (! $this->map->has($key)) {
            return false;
        }

        return (bool) $this->raw($entry, $key, $locale);
    }

    /**
     * An asset field resolved to a URL.
     */
    public function assetUrl(object $entry, string $key, ?string $locale = null): ?string
    {
        $value = $this->raw($entry, $key, $locale);

        if (is_string($value)) {
            return trim($value) !== '' ? trim($value) : null;
        }

        return AssetUrl::from($value);
    }

    /**
     * Whether a value counts as an answer.
     *
     * Whitespace, empty strings, empty arrays and false are all "not set". Only
     * these stop the fallback walk from continuing to the next candidate.
     */
    public static function isFilled(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        if ($value instanceof \Countable) {
            return count($value) > 0;
        }

        return true;
    }
}
