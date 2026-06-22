<?php

declare(strict_types=1);

use SilaSeo\Laravel\MetaService;

if (! function_exists('seo')) {
    /**
     * Resolve the request-scoped SEO meta service.
     */
    function seo(): MetaService
    {
        return app(MetaService::class);
    }
}