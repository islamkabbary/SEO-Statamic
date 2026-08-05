<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Settings;

use Illuminate\Support\Facades\Schema;
use SilaSeo\Laravel\Models\SeoSetting;
use Throwable;

/**
 * Reads site-wide SEO settings as a flat payload, cached forever and busted on
 * write. Returns an empty payload when the table is absent (e.g. pre-migration)
 * so the package is safe to install before migrating.
 */
final class SettingsRepository
{
    public const CACHE_KEY = 'silaseo.settings';

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return cache()->rememberForever(self::CACHE_KEY, function (): array {
            try {
                if (! Schema::hasTable('seo_settings')) {
                    return [];
                }

                return SeoSetting::query()
                    ->get()
                    ->mapWithKeys(static fn (SeoSetting $s): array => [$s->key => $s->value])
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });
    }
}