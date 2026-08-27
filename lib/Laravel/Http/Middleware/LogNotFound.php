<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records 404 responses (URL, referrer, hit count) so the SEO specialist can
 * find broken links and add redirects. Best-effort: never breaks the response.
 */
final class LogNotFound
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404 && $request->isMethod('GET')) {
            $this->record($request);
        }

        return $response;
    }

    private function record(Request $request): void
    {
        try {
            if (! Schema::hasTable('seo_404_log')) {
                return;
            }

            $url = $request->fullUrl();
            $hash = hash('sha256', $url);

            $updated = DB::table('seo_404_log')
                ->where('url_hash', $hash)
                ->update([
                    'hits' => DB::raw('hits + 1'),
                    'referrer' => $request->headers->get('referer'),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                DB::table('seo_404_log')->insert([
                    'url' => mb_substr($url, 0, 2048),
                    'url_hash' => $hash,
                    'referrer' => $request->headers->get('referer'),
                    'hits' => 1,
                    'last_seen_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (Throwable) {
            // 404 logging is best-effort.
        }
    }
}