<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SilaSeo\Statamic\Redirects\GlobalRedirectStore;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies managed redirects (301/302/307/308) and gone (410) rules from the
 * SEO Settings global before the request reaches the front-end.
 */
final class HandleRedirects
{
    private const REDIRECT_STATUSES = [301, 302, 307, 308];

    public function __construct(private readonly GlobalRedirectStore $store)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $rule = $this->store->find($request->path());

            if ($rule !== null) {
                if ($rule['status'] === 410) {
                    abort(410);
                }

                $status = in_array($rule['status'], self::REDIRECT_STATUSES, true) ? $rule['status'] : 301;

                return redirect($rule['to'] ?: '/', $status);
            }
        }

        return $next($request);
    }
}