<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Locale;

use SilaSeo\Core\Support\Locale;
use SilaSeo\Statamic\Fields\FieldMap;
use Statamic\Facades\Site;
use Throwable;

/**
 * Real Statamic multisite: each localization is its own entry, and the site
 * handle identifies the locale.
 *
 * This is what silaeng, sila.vision and alhirfah run, so it must reproduce today's
 * behaviour exactly apart from two corrections that were wrong on every project:
 *
 *  - hreflang was taken from Entry::locale(), which returns the SITE HANDLE, not a
 *    language tag. On a real multisite the handle happens to be `en`/`ar` so it
 *    looked right; the value is now read from the site's declared locale and
 *    normalised to BCP 47, so a site declaring `en_US` emits `en-US` rather than
 *    the invalid `en_US`.
 *
 *  - Entry::sites() lists the COLLECTION's configured sites, not the sites the
 *    entry actually exists in. Only in($handle) can answer that, and it returns
 *    null for a localization that was never created.
 */
final class MultisiteLocaleStrategy implements LocaleStrategy
{
    public function name(): string
    {
        return 'multisite';
    }

    public function current(): string
    {
        try {
            $handle = Site::current()?->handle();

            if (is_string($handle) && $handle !== '') {
                return $handle;
            }
        } catch (Throwable) {
            // Fall through.
        }

        return $this->defaultHandle();
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        try {
            $handles = Site::all()->map(static fn ($site) => $site->handle())->all();

            return array_values(array_filter($handles, static fn ($h): bool => is_string($h) && $h !== ''));
        } catch (Throwable) {
            return [$this->defaultHandle()];
        }
    }

    /**
     * @return list<array{hreflang: string, url: string}>
     */
    public function alternatesFor(object $entry, FieldMap $map): array
    {
        try {
            $handles = $this->siteHandles($entry);

            // A single-site install has nothing to alternate with. Emitting one
            // self-referencing alternate plus a synthesised x-default is noise.
            if (count($handles) < 2) {
                return [];
            }

            $alternates = [];

            foreach ($handles as $handle) {
                $localized = $this->localization($entry, $handle);

                // in() returns null when the localization does not exist. This is
                // the existence check: sites() only lists what the collection is
                // configured for, not what was actually created.
                if ($localized === null) {
                    continue;
                }

                if (! $this->isPublished($localized)) {
                    continue;
                }

                $url = $this->absoluteUrl($localized);

                if ($url === null) {
                    continue;
                }

                $hreflang = $this->hreflangFor($handle);

                if ($hreflang === null) {
                    continue;
                }

                $alternates[] = ['hreflang' => $hreflang, 'url' => $url];
            }

            return $alternates;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Resolve a site handle to a language tag.
     *
     * Site::locale() and Site::lang() both exist in Statamic 4, 5 and 6. The
     * handle is only a last resort, and is rejected outright when it is not a
     * plausible language code — which is what stops a single-site install
     * emitting hreflang="default".
     */
    private function hreflangFor(string $handle): ?string
    {
        try {
            $site = Site::get($handle);

            if ($site !== null) {
                $tag = Locale::hreflang(method_exists($site, 'locale') ? $site->locale() : null)
                    ?? Locale::hreflang(method_exists($site, 'lang') ? $site->lang() : null);

                if ($tag !== null) {
                    return $tag;
                }
            }
        } catch (Throwable) {
            // Fall through to the handle.
        }

        return Locale::hreflang($handle);
    }

    /**
     * @return list<string>
     */
    private function siteHandles(object $entry): array
    {
        if (! method_exists($entry, 'sites')) {
            return [];
        }

        $sites = $entry->sites();
        $handles = is_object($sites) && method_exists($sites, 'all') ? $sites->all() : (array) $sites;

        return array_values(array_filter(
            array_map(static fn (mixed $h): string => is_string($h) ? $h : '', $handles),
            static fn (string $h): bool => $h !== '',
        ));
    }

    private function localization(object $entry, string $handle): ?object
    {
        if (! method_exists($entry, 'in')) {
            return null;
        }

        // in() compares strictly against the locale string; handing it a Site
        // object falls through to descendants()->get($object) and returns null.
        $localized = $entry->in($handle);

        return is_object($localized) ? $localized : null;
    }

    private function isPublished(object $entry): bool
    {
        try {
            return method_exists($entry, 'published') ? (bool) $entry->published() : true;
        } catch (Throwable) {
            return true;
        }
    }

    private function absoluteUrl(object $entry): ?string
    {
        foreach (['absoluteUrl', 'url'] as $method) {
            try {
                if (method_exists($entry, $method)) {
                    $url = $entry->{$method}();

                    if (is_string($url) && $url !== '') {
                        return $url;
                    }
                }
            } catch (Throwable) {
                // Try the next accessor.
            }
        }

        return null;
    }

    private function defaultHandle(): string
    {
        try {
            return (string) (Site::default()?->handle() ?? 'default');
        } catch (Throwable) {
            return 'default';
        }
    }
}
