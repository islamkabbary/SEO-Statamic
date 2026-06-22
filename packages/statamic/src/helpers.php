<?php

declare(strict_types=1);

use SilaSeo\Laravel\MetaService;
use SilaSeo\Statamic\EntrySeo;
use SilaSeo\Statamic\Settings\SettingsReader;

if (! function_exists('silaseo_entry')) {
    /**
     * Apply a Statamic entry's SEO (meta + JSON-LD + hreflang) to the current
     * request, then return the entry for convenience.
     *
     * @template T of object
     *
     * @param T $entry
     *
     * @return T
     */
    function silaseo_entry(object $entry, string $schemaType = 'Article'): object
    {
        app(EntrySeo::class)->apply($entry, $schemaType);

        return $entry;
    }
}

if (! function_exists('silaseo_settings')) {
    /**
     * Apply the site-wide SEO Settings global (defaults + Organization) to the
     * current request's meta service. Call once in the site layout's <head>.
     */
    function silaseo_settings(): void
    {
        app(SettingsReader::class)->applyTo(app(MetaService::class));
    }
}