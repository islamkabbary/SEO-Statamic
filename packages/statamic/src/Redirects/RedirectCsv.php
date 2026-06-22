<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Redirects;

/**
 * Pure CSV codec for redirect rules (`from,to,status`). Framework-agnostic and
 * unit-testable: import is tolerant (skips the header and malformed rows, fills
 * a default status) so a hand-edited file never throws.
 */
final class RedirectCsv
{
    private const ALLOWED_STATUSES = [301, 302, 307, 308, 410];

    private const HEADER = ['from', 'to', 'status'];

    /**
     * @param list<array<string,mixed>> $rows
     */
    public function export(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADER, escape: '');

        foreach ($rows as $row) {
            fputcsv($handle, [
                (string) ($row['from'] ?? ''),
                (string) ($row['to'] ?? ''),
                (string) ($row['status'] ?? 301),
            ], escape: '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @return list<array{from: string, to: string, status: int}>
     */
    public function import(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line, escape: '');
            $from = trim((string) ($cells[0] ?? ''));
            $to = trim((string) ($cells[1] ?? ''));

            if ($from === '' || $this->isHeader($from)) {
                continue;
            }

            $status = (int) ($cells[2] ?? 301);

            if (! in_array($status, self::ALLOWED_STATUSES, true)) {
                $status = 301;
            }

            if ($to === '' && $status !== 410) {
                continue;
            }

            $rows[] = ['from' => $from, 'to' => $to, 'status' => $status];
        }

        return $rows;
    }

    private function isHeader(string $from): bool
    {
        return strtolower($from) === 'from';
    }
}