<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SilaSeo\Laravel\Redirects\RedirectRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves managed redirects (301/302/307/308) and gone (410) rules from the
 * cached redirect table before the request reaches the application.
 */
final class HandleRedirects
{
    private const REDIRECT_STATUSES = [301, 302, 307, 308];

    public function __construct(private readonly RedirectRepository $redirects)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $rule = $this->redirects->find($request->path());

            if ($rule !== null) {
                $this->redirects->recordHit($request->path());

                if ($rule['status'] === 410) {
                    abort(410);
                }

                $status = in_array($rule['status'], self::REDIRECT_STATUSES, true) ? $rule['status'] : 301;

                return redirect()->to($rule['to'] ?? '/', $status);
            }
        }

        return $next($request);
    }
}