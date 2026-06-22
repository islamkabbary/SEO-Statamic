<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Controllers;

use Illuminate\Http\Response;
use SilaSeo\Core\Sitemap\SitemapRenderer;
use SilaSeo\Statamic\Sitemap\SitemapSource;

/**
 * Serves /sitemap.xml: published, indexable entries with hreflang alternates
 * and images.
 */
final class SitemapController
{
    public function __invoke(SitemapSource $source, SitemapRenderer $renderer): Response
    {
        return response($renderer->render($source->urls()), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}