<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Small response helpers shared by the SEO middleware.
 */
final class Responses
{
    public static function isHtml(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return is_string($contentType) && str_contains(strtolower($contentType), 'text/html');
    }
}