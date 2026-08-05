<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Redirects;

use Illuminate\Support\Facades\Schema;
use SilaSeo\Laravel\Models\SeoRedirect;
use Throwable;

/**
 * Caches the redirect table as a path => rule map so redirect resolution costs
 * no query on the hot path. Safe when the table is absent.
 *
 * Both sides of the lookup are normalised through {@see normalise()}: the stored
 * `from` when the map is built, and the request path when it is queried. Editors
 * enter paths by hand, so `about`, `/about` and `/about/` all reach the table;
 * normalising only the query side (as this class used to) meant any row not
 * already stored in canonical form could never be matched.
 *
 * Normalisation is deliberately identical to
 * {@see \SilaSeo\Statamic\Redirects\GlobalRedirectStore::normalize()} so the
 * database-backed and flat-file-backed redirect tables agree on what a path is.
 */
final class RedirectRepository
{
    public const CACHE_KEY = 'silaseo.redirects';

    /**
     * @return array{to: string|null, status: int, from: string}|null
     */
    public function find(string $path): ?array
    {
        return $this->map()[$this->normalise($path)] ?? null;
    }

    public function recordHit(string $path): void
    {
        $rule = $this->find($path);

        if ($rule === null) {
            return;
        }

        try {
            // Match on the stored value, not the normalised one -- the row may have
            // been saved as `about` or `/about/` and would not match `/about`.
            SeoRedirect::query()
                ->where('from', $rule['from'])
                ->increment('hits', 1, ['last_hit_at' => now()]);
        } catch (Throwable) {
            // Hit tracking is best-effort and must never break the redirect.
        }
    }

    public function flush(): void
    {
        try {
            cache()->forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Cache store unavailable; the next read rebuilds anyway.
        }
    }

    /**
     * Keyed by normalised path. Two rows that differ only in surrounding slashes
     * collapse to one key -- the `from` column is unique, so the database cannot
     * prevent that pair from existing; the later row wins.
     *
     * @return array<string, array{to: string|null, status: int, from: string}>
     */
    private function map(): array
    {
        return cache()->rememberForever(self::CACHE_KEY, function (): array {
            try {
                if (! Schema::hasTable('seo_redirects')) {
                    return [];
                }

                return SeoRedirect::query()
                    ->orderBy('id')
                    ->get(['from', 'to', 'status'])
                    ->mapWithKeys(fn (SeoRedirect $r): array => [
                        $this->normalise((string) $r->from) => [
                            'to' => $r->to,
                            'status' => $r->status,
                            'from' => (string) $r->from,
                        ],
                    ])
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });
    }

    private function normalise(string $path): string
    {
        return '/' . trim($path, '/');
    }
}
