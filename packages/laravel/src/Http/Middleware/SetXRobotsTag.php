<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SilaSeo\Laravel\MetaService;
use SilaSeo\Laravel\Support\Responses;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mirrors the resolved robots directives onto an X-Robots-Tag response header
 * for HTML responses, without clobbering a header the app already set.
 */
final class SetXRobotsTag
{
    public function __construct(private readonly MetaService $seo)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Responses::isHtml($response)) {
            return $response;
        }

        try {
            foreach ($this->seo->httpHeaders() as $key => $value) {
                if (! $response->headers->has($key)) {
                    $response->headers->set($key, $value);
                }
            }
        } catch (Throwable) {
            // Never let SEO header resolution break the response.
        }

        return $response;
    }
}