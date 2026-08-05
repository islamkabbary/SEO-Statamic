<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Redirects;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SilaSeo\Laravel\Models\SeoRedirect;
use Throwable;

/**
 * Caches the redirect table as a path => rule map so redirect resolution costs
 * no query on the hot path. Safe when the table is absent.
 */
final class RedirectRepository
{
    private const CACHE_KEY = 'silaseo.redirects';

    /**
     * @return array{to: string|null, status: int}|null
     */
    public function find(string $path): ?array
    {
        return $this->map()[$this->normalise($path)] ?? null;
    }

    public function recordHit(string $path): void
    {
        try {
            SeoRedirect::query()
                ->where('from', $this->normalise($path))
                ->update([
                    'hits' => \DB::raw('hits + 1'),
                    'last_hit_at' => now(),
                ]);
        } catch (Throwable) {
            // Hit tracking is best-effort and must never break the redirect.
        }
    }

    /**
     * @return array<string, array{to: string|null, status: int}>
     */
    private function map(): array
    {
        return cache()->rememberForever(self::CACHE_KEY, function (): array {
            try {
                if (! Schema::hasTable('seo_redirects')) {
                    return [];
                }

                return SeoRedirect::query()
                    ->get(['from', 'to', 'status'])
                    ->mapWithKeys(static fn (SeoRedirect $r): array => [
                        $r->from => ['to' => $r->to, 'status' => $r->status],
                    ])
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });
    }

    private function normalise(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}