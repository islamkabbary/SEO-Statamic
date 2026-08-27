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
 * LAST-RESORT head injector for layouts that cannot be edited. It inserts the
 * rendered SEO block before </head> once (guarded by a marker).
 *
 * It does NOT deduplicate against tags the layout already emits, so only enable
 * it on layouts that have no <title>/<meta>/<link> SEO of their own. Prefer the
 * @seoHead directive or <x-seo-head/> component everywhere else.
 */
final class InjectSeo
{
    private const MARKER = '<!--silaseo:head-->';

    public function __construct(private readonly MetaService $seo)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Responses::isHtml($response)) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '' || str_contains($content, self::MARKER)) {
            return $response;
        }

        if (! str_contains($content, '</head>')) {
            return $response;
        }

        try {
            $head = $this->seo->head();
        } catch (Throwable) {
            return $response;
        }

        if ($head === '') {
            return $response;
        }

        $block = "\n" . self::MARKER . "\n" . $head . "\n<!--/silaseo:head-->\n";
        $injected = preg_replace('/<\/head>/i', $block . '</head>', $content, 1);

        if (is_string($injected)) {
            $response->setContent($injected);
        }

        return $response;
    }
}