<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Locale;

use SilaSeo\Core\Support\Locale;
use SilaSeo\Statamic\Fields\FieldMap;
use SilaSeo\Statamic\Fields\ValueReader;

/**
 * One Statamic site, several languages distinguished by a URL prefix, with each
 * language's content stored in twin handles on the same entry
 * (`title` / `title_ar`).
 *
 * Locale comes from the request path, never from the site config. Two projects in
 * the fleet call session_start() inside config/statamic/sites.php and rewrite the
 * single site's locale from $_SESSION while the config file is being loaded; that
 * value is captured by config:cache and is simply wrong from then on. The URL is
 * the only honest signal, and it is also the one a crawler sees.
 */
final class PrefixLocaleStrategy implements LocaleStrategy
{
    /**
     * @param array<string, array{prefix?: string, hreflang?: string, x_default?: bool}> $locales
     */
    public function __construct(
        private readonly array $locales,
        private readonly ValueReader $reader,
        private readonly ?string $currentUrl = null,
    ) {
    }

    public function forUrl(?string $url): self
    {
        return new self($this->locales, $this->reader, $url);
    }

    public function name(): string
    {
        return 'prefix';
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return array_map(strval(...), array_keys($this->locales));
    }

    public function current(): string
    {
        $segments = $this->segments($this->path());

        // Longest prefix wins, so a two-segment prefix like "en/uk" is not
        // shadowed by "en".
        $best = null;
        $bestLength = -1;

        foreach ($this->locales as $locale => $config) {
            $prefix = $this->segments((string) ($config['prefix'] ?? ''));
            $length = count($prefix);

            if ($length === 0) {
                continue;
            }

            if ($length <= count($segments) && array_slice($segments, 0, $length) === $prefix && $length > $bestLength) {
                $best = (string) $locale;
                $bestLength = $length;
            }
        }

        if ($best !== null) {
            return $best;
        }

        // The unprefixed locale owns the site root.
        foreach ($this->locales as $locale => $config) {
            if ($this->segments((string) ($config['prefix'] ?? '')) === []) {
                return (string) $locale;
            }
        }

        return (string) (array_key_first($this->locales) ?? 'en');
    }

    /**
     * An alternate is emitted only for a locale this entry actually has content
     * for.
     *
     * There is no second entry to look up here -- the same entry is rendered under
     * every prefix -- so existence is decided by the content itself: resolve the
     * `title` handles for that locale through the field map and require a non-empty
     * value. An entry holding only `title_ar` has no English page, and advertising
     * one tells a crawler a translation exists when the visitor would get Arabic.
     *
     * `title` is the probe because a page without one is not a page. Description
     * and image are routinely left blank on pages that certainly exist.
     *
     * @return list<array{hreflang: string, url: string}>
     */
    public function alternatesFor(object $entry, FieldMap $map): array
    {
        if (count($this->locales) < 2) {
            return [];
        }

        $base = $this->canonicalPath();
        $alternates = [];

        foreach ($this->locales as $locale => $config) {
            $locale = (string) $locale;

            if (! $this->hasContentFor($entry, $map, $locale)) {
                continue;
            }

            $hreflang = Locale::hreflang((string) ($config['hreflang'] ?? $locale));

            if ($hreflang === null) {
                continue;
            }

            $url = $this->urlFor($base, (string) ($config['prefix'] ?? ''));

            $alternates[] = ['hreflang' => $hreflang, 'url' => $url];

            if (($config['x_default'] ?? false) === true) {
                $alternates[] = ['hreflang' => 'x-default', 'url' => $url];
            }
        }

        // A lone self-referencing alternate says nothing.
        return count(array_filter($alternates, static fn (array $a): bool => $a['hreflang'] !== 'x-default')) < 2
            ? []
            : $alternates;
    }

    public function hasContentFor(object $entry, FieldMap $map, string $locale): bool
    {
        foreach ($map->candidates('title', $locale) as $handle) {
            $value = $this->reader->read($entry, $handle);

            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function urlFor(string $canonicalPath, string $prefix): string
    {
        $prefix = trim($prefix, '/');
        $path = ltrim($canonicalPath, '/');
        $joined = $prefix === '' ? $path : ($path === '' ? $prefix : $prefix . '/' . $path);

        return rtrim($this->origin(), '/') . '/' . $joined;
    }

    /**
     * The request path with any configured locale prefix removed.
     */
    private function canonicalPath(): string
    {
        $segments = $this->segments($this->path());
        $current = $this->current();
        $prefix = $this->segments((string) ($this->locales[$current]['prefix'] ?? ''));

        if ($prefix !== [] && array_slice($segments, 0, count($prefix)) === $prefix) {
            $segments = array_slice($segments, count($prefix));
        }

        return '/' . implode('/', $segments);
    }

    private function origin(): string
    {
        $url = $this->url();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($scheme) && is_string($host)) {
            $port = parse_url($url, PHP_URL_PORT);

            return $scheme . '://' . $host . (is_int($port) ? ':' . $port : '');
        }

        return '';
    }

    private function path(): string
    {
        $path = parse_url($this->url(), PHP_URL_PATH);

        return is_string($path) ? $path : '/';
    }

    private function url(): string
    {
        if ($this->currentUrl !== null) {
            return $this->currentUrl;
        }

        if (function_exists('url')) {
            try {
                return (string) url()->current();
            } catch (\Throwable) {
                // Fall through.
            }
        }

        return '/';
    }

    /**
     * @return list<string>
     */
    private function segments(string $path): array
    {
        return array_values(array_filter(explode('/', trim($path, '/')), static fn (string $s): bool => $s !== ''));
    }
}
