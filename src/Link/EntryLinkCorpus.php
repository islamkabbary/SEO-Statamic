<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Link;

use Illuminate\Support\Facades\Cache;
use SilaSeo\Core\Link\LinkTarget;
use SilaSeo\Statamic\Fields\FieldResolver;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
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
    private const CACHE_PREFIX = 'silaseo.link_corpus';
    private const GENERATION_KEY = self::CACHE_PREFIX . '.generation';
    private const CACHE_TTL = 600;
    private const MAX_TARGETS = 1000;

    public function __construct(private readonly ?FieldResolver $resolver = null)
    {
    }

    /**
     * @return list<LinkTarget>
     */
    public function targets(): array
    {
        try {
            return Cache::remember($this->cacheKey(), self::CACHE_TTL, fn (): array => $this->build());
        } catch (Throwable) {
            return $this->build();
        }
    }

    /**
     * Everything that changes what {@see build()} returns.
     *
     * This used to be one global key. The corpus filters by locale, and on the
     * projects whose locale comes from the session that meant whichever language
     * happened to warm the cache was served to everyone for the next ten minutes.
     * Site, locale, strategy, field profile and URL origin all change the result,
     * so all five are in the key.
     */
    private function cacheKey(): string
    {
        $strategy = $this->resolver?->localeStrategy();

        return implode('.', [
            self::CACHE_PREFIX,
            'g' . $this->generation(),
            $this->siteHandle(),
            $strategy?->current() ?? 'unknown',
            $strategy?->name() ?? 'none',
            $this->resolver?->map()->signature() ?? 'default',
            substr(hash('xxh128', $this->origin()), 0, 8),
        ]);
    }

    /**
     * Bumped whenever content or configuration changes.
     *
     * A generation counter rather than a key registry or cache tags: every key
     * derived from it is invalidated at once, and it works on every cache store,
     * including the file and database drivers that do not support tagging.
     */
    private function generation(): int
    {
        try {
            $generation = Cache::get(self::GENERATION_KEY);

            return is_numeric($generation) ? (int) $generation : 1;
        } catch (Throwable) {
            return 1;
        }
    }

    public static function flush(): void
    {
        try {
            $current = Cache::get(self::GENERATION_KEY);
            Cache::forever(self::GENERATION_KEY, (is_numeric($current) ? (int) $current : 1) + 1);
        } catch (Throwable) {
            // Cache store unavailable; entries expire on their own TTL.
        }
    }

    private function siteHandle(): string
    {
        try {
            return (string) (Site::current()?->handle() ?? 'default');
        } catch (Throwable) {
            return 'default';
        }
    }

    /**
     * Target URLs are absolute, so a multi-domain install must not share a corpus.
     */
    private function origin(): string
    {
        if (! function_exists('url')) {
            return '';
        }

        try {
            $current = (string) url()->current();
            $scheme = parse_url($current, PHP_URL_SCHEME);
            $host = parse_url($current, PHP_URL_HOST);

            return is_string($scheme) && is_string($host) ? $scheme . '://' . $host : '';
        } catch (Throwable) {
            return '';
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

        // Through the resolver so a project whose title lives in `title_ar` builds
        // an Arabic corpus rather than an empty one.
        $title = (string) ($this->resolver?->string($entry, 'title') ?? $this->firstFilled(
            $this->value($entry, 'seo_title'),
            $this->value($entry, 'title'),
        ));

        $focusKeyword = trim((string) ($this->resolver?->string($entry, 'focus_keyword')
            ?? $this->value($entry, 'seo_focus_keyword')));

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
            if ($this->resolver !== null) {
                // An unmapped `robots` is false, never a stray read of some other
                // handle -- a project without a noindex toggle must not have pages
                // silently dropped from the corpus.
                return $this->resolver->bool($entry, 'robots');
            }

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