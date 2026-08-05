<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fields;

use InvalidArgumentException;

/**
 * Which entry handles carry which piece of SEO meaning, for one project.
 *
 * The package used to hardcode `seo_title`, `seo_description` and friends at every
 * read site. That only works for projects built around the shipped fieldset; a
 * project that already stores its meta description in `description`, its canonical
 * in `canonical_link`, and keeps a second Arabic copy of each in a `_ar` twin has
 * no way to adopt the package without rewriting its content.
 *
 * The map is data, resolved from config. Nothing here — and nothing that consumes
 * it — branches on a project name.
 */
final class FieldMap
{
    /**
     * Every logical field the package can read. A profile may leave any of them
     * unmapped, which means "this project has no such field" — not "read null".
     */
    public const KEYS = [
        'title',
        'description',
        'image',
        'canonical',
        'robots',
        'focus_keyword',
        'content',
        'schema_type',
        'schema_json',
    ];

    /**
     * @param array<string, list<string>> $handles      logical key => ordered handles, most specific first
     * @param array<string, string>       $suffixes     locale => handle suffix ('' means the bare handle)
     * @param array<string, string>       $defaults     logical key => literal fallback value
     * @param list<string>                $suffixable   logical keys that have per-locale twins
     */
    private function __construct(
        private readonly array $handles,
        private readonly array $suffixes,
        private readonly array $defaults,
        private readonly array $suffixable,
        private readonly ?string $legacyMeta,
        private readonly string $localeStrategy,
    ) {
    }

    /**
     * @param array<string, mixed> $profile
     */
    public static function fromArray(array $profile): self
    {
        $handles = [];

        /** @var array<string, mixed> $fields */
        $fields = is_array($profile['fields'] ?? null) ? $profile['fields'] : [];
        /** @var array<string, mixed> $fallbacks */
        $fallbacks = is_array($profile['fallbacks'] ?? null) ? $profile['fallbacks'] : [];

        foreach (self::KEYS as $key) {
            $ordered = [];

            foreach ([$fields[$key] ?? null, ...(array) ($fallbacks[$key] ?? [])] as $handle) {
                if (is_string($handle) && trim($handle) !== '' && ! in_array($handle, $ordered, true)) {
                    $ordered[] = trim($handle);
                }
            }

            $handles[$key] = $ordered;
        }

        $suffixes = [];

        foreach ((array) ($profile['suffixes'] ?? []) as $locale => $suffix) {
            if (is_string($suffix)) {
                $suffixes[strtolower((string) $locale)] = $suffix;
            }
        }

        $defaults = [];

        foreach ((array) ($profile['defaults'] ?? []) as $key => $value) {
            if (in_array($key, self::KEYS, true) && is_scalar($value)) {
                $defaults[(string) $key] = (string) $value;
            }
        }

        $suffixable = [];

        foreach ((array) ($profile['suffixable'] ?? self::KEYS) as $key) {
            if (in_array($key, self::KEYS, true)) {
                $suffixable[] = (string) $key;
            }
        }

        $legacyMeta = $profile['legacy_meta'] ?? null;

        return new self(
            handles: $handles,
            suffixes: $suffixes,
            defaults: $defaults,
            suffixable: $suffixable,
            legacyMeta: is_string($legacyMeta) && trim($legacyMeta) !== '' ? trim($legacyMeta) : null,
            localeStrategy: is_string($profile['locale_strategy'] ?? null) ? $profile['locale_strategy'] : 'multisite',
        );
    }

    private function assertKey(string $key): void
    {
        if (! in_array($key, self::KEYS, true)) {
            throw new InvalidArgumentException("Unknown SEO field key [{$key}].");
        }
    }

    /**
     * The base handles for a logical key, most specific first, without any suffix.
     *
     * @return list<string>
     */
    public function handles(string $key): array
    {
        $this->assertKey($key);

        return $this->handles[$key] ?? [];
    }

    public function has(string $key): bool
    {
        return $this->handles($key) !== [];
    }

    /**
     * The full ordered list of concrete handles to try for a logical key.
     *
     * Locale-specific handles come first — ALL of them — before any bare handle.
     * A reader asking for Arabic should get the Arabic fallback field in preference
     * to the English primary one; doing it the other way round means an entry with
     * `seo_title` (English) and `title_ar` (Arabic) serves the English title to
     * Arabic visitors.
     *
     * @return list<string>
     */
    public function candidates(string $key, string $locale): array
    {
        $handles = $this->handles($key);
        $suffix = $this->suffixFor($locale);

        if ($suffix === '' || ! in_array($key, $this->suffixable, true)) {
            return $handles;
        }

        $candidates = [];

        foreach ($handles as $handle) {
            $candidates[] = $handle . $suffix;
        }

        foreach ($handles as $handle) {
            if (! in_array($handle, $candidates, true)) {
                $candidates[] = $handle;
            }
        }

        return $candidates;
    }

    /**
     * The handle suffix for a locale. An unconfigured locale has no suffix, which
     * makes the bare handle the only candidate — the safe reading.
     */
    public function suffixFor(string $locale): string
    {
        return $this->suffixes[strtolower($locale)] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function suffixes(): array
    {
        return $this->suffixes;
    }

    public function defaultFor(string $key): ?string
    {
        $this->assertKey($key);

        return $this->defaults[$key] ?? null;
    }

    /**
     * A freeform field holding hand-written meta markup from before the project
     * adopted structured SEO fields. Read only as a last resort.
     */
    public function legacyMetaHandle(): ?string
    {
        return $this->legacyMeta;
    }

    public function localeStrategy(): string
    {
        return $this->localeStrategy;
    }

    /**
     * Every concrete handle this map can read, including suffixed variants.
     *
     * Used to keep SEO fields out of the body text the content analyser scores:
     * feeding the meta description back in as prose skews keyword density and
     * readability.
     *
     * @return list<string>
     */
    public function allHandles(): array
    {
        $all = [];

        foreach (self::KEYS as $key) {
            foreach ($this->handles($key) as $handle) {
                $all[] = $handle;

                foreach ($this->suffixes as $suffix) {
                    if ($suffix !== '') {
                        $all[] = $handle . $suffix;
                    }
                }
            }
        }

        if ($this->legacyMeta !== null) {
            $all[] = $this->legacyMeta;

            foreach ($this->suffixes as $suffix) {
                if ($suffix !== '') {
                    $all[] = $this->legacyMeta . $suffix;
                }
            }
        }

        return array_values(array_unique($all));
    }

    /**
     * A stable fingerprint of everything that changes what a read returns.
     *
     * Cached derivatives — the internal-link corpus above all — must not survive a
     * profile change, or a site keeps serving results computed from handles it no
     * longer uses.
     */
    public function signature(): string
    {
        return substr(hash('xxh128', (string) json_encode([
            $this->handles,
            $this->suffixes,
            $this->defaults,
            $this->suffixable,
            $this->legacyMeta,
            $this->localeStrategy,
        ])), 0, 12);
    }
}
