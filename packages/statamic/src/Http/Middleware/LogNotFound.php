<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records front-end 404s (path + count + last seen) to a flat YAML log so the
 * SEO specialist can spot broken links and add redirects. Best-effort.
 */
final class LogNotFound
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404 && $request->isMethod('GET')) {
            $this->record('/' . trim($request->path(), '/'));
        }

        return $response;
    }

    private function record(string $path): void
    {
        try {
            $file = storage_path('silaseo/404_log.json');
            File::ensureDirectoryExists(dirname($file));

            $log = File::exists($file) ? (array) json_decode(File::get($file), true) : [];
            $log[$path] = [
                'hits' => (int) ($log[$path]['hits'] ?? 0) + 1,
                'last_seen' => now()->toDateTimeString(),
            ];

            File::put($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (Throwable) {
            // 404 logging is best-effort.
        }
    }
}