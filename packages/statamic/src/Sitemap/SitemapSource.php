<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Sitemap;

use SilaSeo\Core\Sitemap\SitemapUrl;
use Statamic\Facades\Entry;
use Throwable;

/**
 * Builds the sitemap from published Statamic entries across ALL sites. Entry
 * queries are current-site scoped, so we walk each site to collect every
 * localization, then group localizations by their origin id to emit cross-linked
 * hreflang alternates. Skips seo_noindex / URL-less entries. Defensive.
 */
final class SitemapSource
{
    /**
     * @return list<SitemapUrl>
     */
    public function urls(): array
    {
        /** @var array<string,array<string,string>> $groups origin id => (hreflang => url) */
        $groups = [];
        /** @var list<array{url:string,group:string,lastmod:?string,images:list<string>}> $items */
        $items = [];

        foreach ($this->allEntries() as $entry) {
            if (! $this->isIndexable($entry)) {
                continue;
            }

            $url = $this->absoluteUrl($entry);

            if ($url === null) {
                continue;
            }

            $group = $this->groupId($entry);
            $groups[$group][$this->locale($entry) ?? $group] = $url;

            $items[] = [
                'url' => $url,
                'group' => $group,
                'lastmod' => $this->lastmod($entry),
                'images' => $this->images($entry),
            ];
        }

        $urls = [];

        foreach ($items as $item) {
            $urls[] = new SitemapUrl($item['url'], $item['lastmod'], $groups[$item['group']] ?? [], $item['images']);
        }

        return $urls;
    }

    /**
     * Published entries (current/default site). Cross-site localization coverage
     * depends on the project's multisite setup; page-level hreflang remains the
     * authoritative signal.
     *
     * @return list<object>
     */
    private function allEntries(): array
    {
        try {
            return array_values(Entry::query()->where('published', true)->get()->all());
        } catch (Throwable) {
            return [];
        }
    }

    private function groupId(object $entry): string
    {
        try {
            if (method_exists($entry, 'origin')) {
                $origin = $entry->origin();

                if (is_object($origin) && method_exists($origin, 'id')) {
                    return (string) $origin->id();
                }
            }
        } catch (Throwable) {
            // Fall through to own id.
        }

        try {
            if (method_exists($entry, 'id')) {
                return (string) $entry->id();
            }
        } catch (Throwable) {
            // Fall through.
        }

        return spl_object_hash($entry);
    }

    private function isIndexable(object $entry): bool
    {
        try {
            return ! (method_exists($entry, 'value') && (bool) $entry->value('seo_noindex'));
        } catch (Throwable) {
            return true;
        }
    }

    private function absoluteUrl(object $entry): ?string
    {
        try {
            if (method_exists($entry, 'absoluteUrl')) {
                $url = $entry->absoluteUrl();

                return is_string($url) && $url !== '' ? $url : null;
            }
        } catch (Throwable) {
            // Fall through.
        }

        return null;
    }

    private function lastmod(object $entry): ?string
    {
        try {
            if (method_exists($entry, 'lastModified')) {
                return $entry->lastModified()?->toAtomString();
            }
        } catch (Throwable) {
            // Fall through.
        }

        return null;
    }

    private function locale(object $entry): ?string
    {
        try {
            return method_exists($entry, 'locale') ? $entry->locale() : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function images(object $entry): array
    {
        try {
            $value = $entry->augmentedValue('seo_image')->value();

            if (is_object($value) && method_exists($value, 'first')) {
                $value = $value->first();
            }

            if (is_object($value) && method_exists($value, 'absoluteUrl')) {
                return [$value->absoluteUrl()];
            }

            if (is_object($value) && method_exists($value, 'url')) {
                return [$value->url()];
            }
        } catch (Throwable) {
            // No image.
        }

        return [];
    }
}