<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\NotFound;

use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Reads (and clears) the flat-file 404 log written by {@see \SilaSeo\Statamic\Http\Middleware\LogNotFound},
 * exposing it as a hits-sorted list for the Control Panel viewer.
 */
final class NotFoundLog
{
    public function path(): string
    {
        return storage_path('silaseo/404_log.json');
    }

    /**
     * @return list<array{path: string, hits: int, last_seen: ?string}>
     */
    public function entries(): array
    {
        try {
            if (! File::exists($this->path())) {
                return [];
            }

            $log = json_decode(File::get($this->path()), true);

            if (! is_array($log)) {
                return [];
            }

            $rows = [];

            foreach ($log as $path => $meta) {
                if (! is_array($meta)) {
                    continue;
                }

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
            if (File::exists($this->path())) {
                File::delete($this->path());
            }
        } catch (Throwable) {
            // Best-effort.
        }
    }
}