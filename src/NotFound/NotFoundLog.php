<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\NotFound;

use Throwable;

/**
 * The flat-file 404 log: written by {@see \SilaSeo\Statamic\Http\Middleware\LogNotFound}
 * on every front-end miss, read by the Control Panel viewer.
 *
 * Recording used to be an unlocked read-modify-write of the whole file, with no
 * bound on its size. On a public site that is not merely untidy: automated
 * scanners probe hundreds of distinct URLs a minute, so the file grew without
 * limit while every subsequent 404 read, decoded, re-encoded and rewrote all of
 * it, and concurrent misses silently clobbered each other's counts.
 *
 * Three things fix that, and all three matter:
 *  - an exclusive lock held across the whole read-modify-write,
 *  - a hard entry cap that keeps the most-hit paths and discards the tail,
 *  - a deny-list, so scanner noise never consumes the cap in the first place.
 *
 * Uses plain filesystem functions rather than the File facade so the log can be
 * exercised without booting an application.
 */
final class NotFoundLog
{
    /**
     * Beyond this many distinct paths the least-hit entries are dropped. A human
     * reviewing broken links never reads past the first page; the cap is what
     * keeps a scanner from turning this into an unbounded file.
     */
    public const MAX_ENTRIES = 500;

    private const MAX_PATH_LENGTH = 2048;

    /**
     * Requests that are hostile probes rather than broken links. Logging them
     * buries the real 404s an editor needs to see and burns the entry cap.
     */
    private const IGNORED_PATTERNS = [
        '#\.(php|phtml|asp|aspx|jsp|cgi|pl|sh|sql|bak|old|swp|zip|rar|7z|gz|tar)$#i',
        '#(^|/)(wp-admin|wp-content|wp-includes|wp-login|xmlrpc|phpmyadmin|administrator|cgi-bin|autodiscover)(/|$)#i',
        '#(^|/)\.(env|git|svn|aws|ssh|htaccess|htpasswd)#i',
        '#(^|/)vendor/#i',
    ];

    public function __construct(private readonly ?string $path = null)
    {
    }

    public function path(): string
    {
        return $this->path ?? storage_path('silaseo/404_log.json');
    }

    /**
     * Whether a path is worth recording at all.
     */
    public function shouldRecord(string $path): bool
    {
        foreach (self::IGNORED_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Increment the hit count for a path. Best-effort: a failure here must never
     * turn a 404 into a 500.
     */
    public function record(string $path): void
    {
        $path = mb_substr($path, 0, self::MAX_PATH_LENGTH);

        if ($path === '' || ! $this->shouldRecord($path)) {
            return;
        }

        $handle = null;

        try {
            $file = $this->path();
            $directory = dirname($file);

            if (! is_dir($directory) && ! @mkdir($directory, 0o755, true) && ! is_dir($directory)) {
                return;
            }

            // 'c+' creates when missing and, unlike 'w+', does not truncate before
            // the lock is held -- truncating first would lose the file's contents
            // to any process that is still waiting.
            $handle = @fopen($file, 'c+');

            if ($handle === false) {
                return;
            }

            if (! flock($handle, LOCK_EX)) {
                return;
            }

            $log = $this->decode((string) stream_get_contents($handle));

            $log[$path] = [
                'hits' => (int) ($log[$path]['hits'] ?? 0) + 1,
                // date() rather than now(): keeps the log usable outside an
                // application. Laravel sets PHP's default timezone from
                // app.timezone, so the rendered value agrees either way.
                'last_seen' => date('Y-m-d H:i:s'),
            ];

            $log = $this->cap($log);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            fflush($handle);
        } catch (Throwable) {
            // 404 logging is best-effort.
        } finally {
            if (is_resource($handle)) {
                @flock($handle, LOCK_UN);
                @fclose($handle);
            }
        }
    }

    /**
     * @return list<array{path: string, hits: int, last_seen: ?string}>
     */
    public function entries(): array
    {
        try {
            if (! is_file($this->path())) {
                return [];
            }

            $rows = [];

            foreach ($this->decode((string) @file_get_contents($this->path())) as $path => $meta) {
                $rows[] = [
                    'path' => (string) $path,
                    'hits' => (int) ($meta['hits'] ?? 0),
                    'last_seen' => isset($meta['last_seen']) ? (string) $meta['last_seen'] : null,
                ];
            }

            usort($rows, static fn (array $a, array $b): int => $b['hits'] <=> $a['hits']);

            return $rows;
        } catch (Throwable) {
            return [];
        }
    }

    public function clear(): void
    {
        try {
            if (is_file($this->path())) {
                @unlink($this->path());
            }
        } catch (Throwable) {
            // Best-effort.
        }
    }

    /**
     * @param array<string, array{hits: int, last_seen: string}> $log
     *
     * @return array<string, array{hits: int, last_seen: string}>
     */
    private function cap(array $log): array
    {
        if (count($log) <= self::MAX_ENTRIES) {
            return $log;
        }

        // Keep the most-hit paths: those are the broken links worth fixing, and
        // a one-off probe is exactly what should fall off the end.
        uasort($log, static fn (array $a, array $b): int => $b['hits'] <=> $a['hits']);

        return array_slice($log, 0, self::MAX_ENTRIES, true);
    }

    /**
     * @return array<string, array{hits: int, last_seen: string}>
     */
    private function decode(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $clean = [];

        foreach ($decoded as $path => $meta) {
            if (is_array($meta)) {
                $clean[(string) $path] = [
                    'hits' => (int) ($meta['hits'] ?? 0),
                    'last_seen' => (string) ($meta['last_seen'] ?? ''),
                ];
            }
        }

        return $clean;
    }
}
