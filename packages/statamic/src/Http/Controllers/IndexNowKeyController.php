<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Controllers;

use Illuminate\Http\Response;
use SilaSeo\Statamic\IndexNow\IndexNowConfig;

/**
 * Serves the IndexNow key verification file at /{key}.txt. Responds only when
 * the requested key matches the configured one; otherwise 404 (so the route
 * never leaks the key or shadows other paths).
 */
class IndexNowKeyController
{
    public function __construct(private readonly IndexNowConfig $config)
    {
    }

    public function __invoke(string $key): Response
    {
        $configured = $this->config->key();

        abort_unless($configured !== '' && hash_equals($configured, $key), 404);

        return response($configured, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}