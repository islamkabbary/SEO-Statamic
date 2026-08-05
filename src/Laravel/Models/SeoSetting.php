<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A site-wide SEO setting (the Laravel analogue of the Statamic seo_settings
 * global). Values are JSON-encoded so scalars, lists, and maps are all valid.
 *
 * @property string $key
 * @property mixed $value
 */
class SeoSetting extends Model
{
    protected $table = 'seo_settings';

    protected $guarded = [];

    /**
     * @var array<string,string>
     */
    protected $casts = ['value' => 'array'];

    protected static function booted(): void
    {
        static::saved(static fn () => cache()->forget('silaseo.settings'));
        static::deleted(static fn () => cache()->forget('silaseo.settings'));
    }
}