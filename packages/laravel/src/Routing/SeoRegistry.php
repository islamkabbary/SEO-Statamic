<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Routing;

use Illuminate\Http\Request;

/**
 * Holds SEO payloads for static and Livewire-full-page routes that have no
 * model. Keys beginning with "/" are matched against the request path; all
 * others are matched against the route name.
 *
 * Registered once at boot (a singleton), so it persists across requests under
 * Octane; per-request overrides live on the request-scoped MetaService instead.
 */
final class SeoRegistry
{
    /** @var array<string,array<string,mixed>> */
    private array $byName = [];

    /** @var array<string,array<string,mixed>> */
    private array $byPath = [];

    public function for(string $key): PendingSeo
    {
        return new PendingSeo($this, $key);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function register(string $key, array $payload): void
    {
        if (str_starts_with($key, '/')) {
            $this->byPath[ltrim($key, '/')] = $payload;

            return;
        }

        $this->byName[$key] = $payload;
    }

    /**
     * Look up a payload by an explicit route name or "/path" key.
     *
     * @return array<string,mixed>
     */
    public function payload(string $key): array
    {
        if (str_starts_with($key, '/')) {
            return $this->byPath[ltrim($key, '/')] ?? [];
        }

        return $this->byName[$key] ?? [];
    }

    /**
     * Resolve the payload for the current request (route name first, then path).
     *
     * @return array<string,mixed>
     */
    public function payloadForRequest(Request $request): array
    {
        $name = $request->route()?->getName();

        if ($name !== null && isset($this->byName[$name])) {
            return $this->byName[$name];
        }

        return $this->byPath[ltrim($request->path(), '/')] ?? [];
    }
}