<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Link;

use Illuminate\Support\Facades\Cache;
use SilaSeo\Core\Link\LinkTarget;
use Statamic\Facades\Entry;
use Throwable;

/**
 * Builds the internal-linking corpus from published Statamic entries: one
 * {@see LinkTarget} per localization, carrying the focus keyword and title as
 * the phrases that make it a relevant link. Cached because it scans the whole
 * content tree; the suggester filters the corpus by locale.
 *
 * Multisite note: localizations are gathered via {@see in()} where the project's
 * setup exposes them (same caveat as the sitemap); otherwise the corpus reflects
 * the queried site only.
 */
final class EntryLinkCorpus
{
    private const CACHE_KEY = 'silaseo.link_corpus';
    private const CACHE_TTL = 600;
    private const MAX_TARGETS = 1000;

    /**
     * @return list<LinkTarget>
     */
    public function targets(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => $this->build());
        } catch (Throwable) {
            return $this->build();
        }
    }

    public static function flush(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Cache store unavailable; nothing to flush.
        }
    }

    /**
     * @return list<LinkTarget>
     */
    private function build(): array
    {
        /** @var array<string,LinkTarget> $targets keyed by localization id to dedupe */
        $targets = [];

        foreach ($this->entries() as $entry) {
            foreach ($this->localizations($entry) as $localization) {
                $target = $this->toTarget($localization);

                if ($target !== null) {
                    $targets[$target->id] = $target;
                }
            }

            if (count($targets) >= self::MAX_TARGETS) {
                break;
            }
        }

        return array_values($targets);
    }

    /**
     * @return list<object>
     */
    private function entries(): array
    {
        try {
            return array_values(Entry::query()->where('published', true)->get()->all());
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * The entry plus its siblings in every site it exists in (best-effort).
     *
     * @return list<object>
     */
    private function localizations(object $entry): array
    {
        $localizations = [$entry];

        try {
            $sites = method_exists($entry, 'sites') ? $entry->sites() : null;
            $handles = is_object($sites) && method_exists($sites, 'all') ? $sites->all() : (is_array($sites) ? $sites : []);

            foreach ($handles as $handle) {
                $sibling = method_exists($entry, 'in') ? $entry->in($handle) : null;

                if (is_object($sibling)) {
                    $localizations[] = $sibling;
                }
            }
        } catch (Throwable) {
            // Single-site or no localizations resolvable.
        }

        return $localizations;
    }

    private function toTarget(object $entry): ?LinkTarget
    {
        if ($this->isNoindex($entry)) {
            return null;
        }

        $id = $this->scalarFrom(fn (): mixed => $entry->id());
        $url = $this->url($entry);

        if ($id === '' || $url === '') {
            return null;
        }

        $title = $this->firstFilled($this->value($entry, 'seo_title'), $this->value($entry, 'title'));
        $focusKeyword = trim($this->value($entry, 'seo_focus_keyword'));

        $keyphrases = [];

        if ($focusKeyword !== '') {
            $keyphrases[] = $focusKeyword;
        }

        // Use the title only when it is topical (multi-word); single-word titles
        // are navigational ("Home", "Blog", "الخدمات") and create noisy matches.
        if ($this->wordCount($title) >= 2) {
            $keyphrases[] = $title;
        }

        if ($keyphrases === []) {
            return null;
        }

        return new LinkTarget($id, $title, $url, $keyphrases, $this->locale($entry));
    }

    private function isNoindex(object $entry): bool
    {
        try {
            return method_exists($entry, 'value') && (bool) $entry->value('seo_noindex');
        } catch (Throwable) {
            return false;
        }
    }

    private function url(object $entry): string
    {
        foreach (['url', 'absoluteUrl'] as $method) {
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

        return '';
    }

    private function value(object $entry, string $handle): string
    {
        try {
            $value = method_exists($entry, 'value') ? $entry->value($handle) : null;

            return is_string($value) ? $value : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function locale(object $entry): string
    {
        try {
            return method_exists($entry, 'locale') ? (string) $entry->locale() : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function scalarFrom(callable $accessor): string
    {
        try {
            $value = $accessor();

            return is_scalar($value) ? (string) $value : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function firstFilled(string ...$values): string
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }

    private function wordCount(string $text): int
    {
        return preg_match_all('/\p{L}[\p{L}\p{M}\p{N}]*/u', $text);
    }
}