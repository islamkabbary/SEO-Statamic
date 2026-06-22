<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tags;

use SilaSeo\Laravel\MetaService;
use Statamic\Tags\Tags;

/**
 * Antlers tag: {{ seo:head }} (or {{ seo }}) renders the resolved SEO <head>
 * block for the current page.
 */
class SeoTag extends Tags
{
    protected static $handle = 'seo';

    public function index(): string
    {
        return $this->head();
    }

    public function head(): string
    {
        return app(MetaService::class)->head();
    }
}