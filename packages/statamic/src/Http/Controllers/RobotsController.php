<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Controllers;

use Illuminate\Http\Response;
use SilaSeo\Statamic\Redirects\GlobalRedirectStore;

/**
 * Serves /robots.txt from the SEO Settings global, falling back to a permissive
 * default when none is set.
 */
final class RobotsController
{
    public function __invoke(GlobalRedirectStore $store): Response
    {
        $body = $store->robotsTxt() ?? "User-agent: *\nDisallow:\n";

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}