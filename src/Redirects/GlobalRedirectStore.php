<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Redirects;

use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Throwable;

/**
 * Reads redirect rules and the robots.txt body from the `seo_settings` Statamic
 * global (flat-file, CP-editable via the grid field). Statamic's Stache caches
 * the global, so no extra caching is needed here.
 */
final class GlobalRedirectStore
{
    /**
     * @return array{to: string|null, status: int}|null
     */
    public function find(string $path): ?array
    {
        $needle = $this->normalize($path);

        foreach ($this->rows() as $row) {
            $from = is_string($row['from'] ?? null) ? $row['from'] : '';

            if ($from !== '' && $this->normalize($from) === $needle) {
                return [
                    'to' => is_string($row['to'] ?? null) ? $row['to'] : null,
                    'status' => (int) ($row['status'] ?? 301),
                ];
            }
        }

        return null;
    }

    public function robotsTxt(): ?string
    {
        $value = $this->global()['robots_txt'] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * All redirect rules, normalized.
     *
     * @return list<array{from: string, to: string, status: int}>
     */
    public function all(): array
    {
        $rows = [];

        foreach ($this->rows() as $row) {
            $from = is_string($row['from'] ?? null) ? trim($row['from']) : '';

            if ($from === '') {
                continue;
            }

            $rows[] = [
                'from' => $from,
                'to' => is_string($row['to'] ?? null) ? trim($row['to']) : '',
                'status' => (int) ($row['status'] ?? 301),
            ];
        }

        return $rows;
    }

    /**
     * Merge incoming rules into the stored set, keyed by normalized `from` so a
     * re-import updates rather than duplicates. Returns the number of new rules.
     *
     * @param list<array{from: string, to: string, status: int}> $incoming
     */
    public function merge(array $incoming): int
    {
        $byKey = [];

        foreach ($this->all() as $row) {
            $byKey[$this->normalize($row['from'])] = $row;
        }

        $added = 0;

        foreach ($incoming as $row) {
            $key = $this->normalize($row['from']);

            if (! isset($byKey[$key])) {
                $added++;
            }

            $byKey[$key] = $row;
        }

        $this->save(array_values($byKey));

        return $added;
    }

    /**
     * @param list<array{from: string, to: string, status: int}> $rows
     */
    public function save(array $rows): void
    {
        try {
            $set = GlobalSet::find('seo_settings');

            if ($set === null) {
                return;
            }

            $grid = array_map(static fn (array $row): array => [
                'from' => $row['from'],
                'to' => $row['to'],
                'status' => (string) $row['status'],
            ], $rows);

            $localized = $set->in(Site::default()->handle());
            $localized->set('redirects', $grid);
            $localized->save();
        } catch (Throwable) {
            // Persisting is best-effort; surface no fatal error to the CP.
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function rows(): array
    {
        $redirects = $this->global()['redirects'] ?? null;

        return is_array($redirects) ? array_values(array_filter($redirects, 'is_array')) : [];
    }

    /**
     * @return array<string,mixed>
     */
    private function global(): array
    {
        try {
            $set = GlobalSet::find('seo_settings');

            return $set ? (array) $set->inCurrentSite()->data()->all() : [];
        } catch (Throwable) {
            return [];
        }
    }

    private function normalize(string $path): string
    {
        return '/' . trim($path, '/');
    }
}