<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

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
        static::saved(static fn () => cache()->forget('silaseo.redirects'));
        static::deleted(static fn () => cache()->forget('silaseo.redirects'));
    }
}