<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SilaSeo\Statamic\NotFound\NotFoundLog;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records front-end 404s (path + count + last seen) so the SEO specialist can
 * spot broken links and add redirects.
 *
 * The storage concerns -- locking, the entry cap, and filtering out scanner
 * noise -- belong to {@see NotFoundLog}, which also reads the log back for the
 * Control Panel; keeping both sides in one class is what stops the writer and
 * the reader disagreeing about the format.
 */
final class LogNotFound
{
    public function __construct(private readonly NotFoundLog $log)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404 && $request->isMethod('GET')) {
            $this->log->record('/' . trim($request->path(), '/'));
        }

        return $response;
    }
}
