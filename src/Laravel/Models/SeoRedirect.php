<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use SilaSeo\Laravel\Redirects\RedirectRepository;

/**
 * A managed redirect or gone (410) rule keyed by source path.
 *
 * @property string $from
 * @property string|null $to
 * @property int $status
 * @property int $hits
 */
class SeoRedirect extends Model
{
    protected $table = 'seo_redirects';

    protected $guarded = [];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'status' => 'integer',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Keyed off the repository's own constant so the cache key cannot drift
        // out of sync with the reader and leave stale redirects served forever.
        static::saved(static fn () => cache()->forget(RedirectRepository::CACHE_KEY));
        static::deleted(static fn () => cache()->forget(RedirectRepository::CACHE_KEY));
    }
}