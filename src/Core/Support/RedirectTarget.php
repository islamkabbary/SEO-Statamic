<?php

declare(strict_types=1);

namespace SilaSeo\Core\Support;

/**
 * Decides whether a redirect rule would send a request back to where it already is.
 *
 * Nothing stops an editor saving `/about -> /about`, and the CSV bulk import makes
 * it easy to do by accident across many rows at once. Without a guard the browser
 * follows the rule, arrives at the same path, matches the same rule, and the page
 * dies with ERR_TOO_MANY_REDIRECTS -- a self-inflicted outage on whatever URL was
 * mistyped.
 *
 * Path comparison is slash-insensitive to match how redirect tables are looked up,
 * so `/about` and `about/` are recognised as the same destination.
 */
final class RedirectTarget
{
    /**
     * @param string      $to          The rule's target: a path, or an absolute URL.
     * @param string      $requestPath The current request path.
     * @param string|null $requestHost The current request host, when known. An
     *                                 absolute target on a different host cannot
     *                                 loop back through this application.
     */
    public static function pointsAtSelf(string $to, string $requestPath, ?string $requestHost = null): bool
    {
        $to = trim($to);
        $current = self::normalisePath($requestPath);

        // An empty target falls back to the site root, which only loops at the root.
        if ($to === '') {
            return $current === '/';
        }

        if (self::isAbsolute($to)) {
            $host = parse_url($to, PHP_URL_HOST);

            if (! is_string($host) || $requestHost === null) {
                return false;
            }

            if (strcasecmp($host, $requestHost) !== 0) {
                return false;
            }

            $path = parse_url($to, PHP_URL_PATH);

            return self::normalisePath(is_string($path) ? $path : '/') === $current;
        }

        // Protocol-relative (//host/path) is another host's problem.
        if (str_starts_with($to, '//')) {
            return false;
        }

        return self::normalisePath(self::stripQueryAndFragment($to)) === $current;
    }

    private static function isAbsolute(string $to): bool
    {
        return preg_match('#^[a-z][a-z0-9+.\-]*://#i', $to) === 1;
    }

    private static function stripQueryAndFragment(string $to): string
    {
        return (string) preg_replace('/[?#].*$/', '', $to);
    }

    private static function normalisePath(string $path): string
    {
        return '/' . trim($path, '/');
    }
}
