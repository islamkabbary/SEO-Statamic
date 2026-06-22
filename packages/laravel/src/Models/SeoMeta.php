<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Per-record SEO overrides. A row attaches to an Eloquent model (polymorphic)
 * or to a route key, optionally scoped to a locale.
 *
 * @property array<string,mixed> $payload
 * @property string|null $locale
 * @property string|null $route_key
 */
class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $guarded = [];

    /**
     * @var array<string,string>
     */
    protected $casts = ['payload' => 'array'];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}