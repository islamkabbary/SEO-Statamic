<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SilaSeo\Core\Support\RedirectTarget;
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
                // 410 is terminal, not a redirect, so it is settled before the
                // loop guard -- a gone rule on the site root has no target to
                // compare and must still return 410.
                if ($rule['status'] === 410) {
                    $this->redirects->recordHit($request->path());

                    abort(410);
                }

                $to = (string) ($rule['to'] ?? '');

                // A rule pointing at its own source would bounce the browser until
                // it gives up. Fall through to the app instead of taking the URL down.
                if (! RedirectTarget::pointsAtSelf($to, $request->path(), $request->getHost())) {
                    $this->redirects->recordHit($request->path());

                    $status = in_array($rule['status'], self::REDIRECT_STATUSES, true) ? $rule['status'] : 301;

                    return redirect()->to($to ?: '/', $status);
                }
            }
        }

        return $next($request);
    }
}