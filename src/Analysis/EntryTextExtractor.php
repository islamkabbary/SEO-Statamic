<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Analysis;

/**
 * Best-effort plain-text extraction from a Statamic entry's data array, used to
 * feed the content analyzer. Walks nested structures (replicators, Bard docs)
 * and keeps sentence-like strings while skipping structural tokens and the SEO
 * meta fields themselves.
 *
 * Known limitation: Bard/replicator structure (headings, links, images) is not
 * reconstructed, so structural checks under-report on rich-text bodies.
 */
final class EntryTextExtractor
{
    private const SKIP_KEYS = [
        'id', 'blueprint', 'type', 'enabled', 'collection', 'slug', 'template', 'layout',
        'published', 'updated_by', 'updated_at', 'created_at', 'date', 'parent', 'origin',
        'seo_title', 'seo_description', 'seo_canonical', 'seo_noindex', 'seo_focus_keyword',
        'seo_image', 'seo_report', 'seo_schema_type', 'seo_schema_json',
    ];

    /**
     * @param array<string,mixed> $data
     */
    public static function extract(array $data): string
    {
        $parts = [];
        self::walk($data, $parts);

        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', $parts)));
    }

    /**
     * @param mixed             $value
     * @param list<string>      $parts
     */
    private static function walk(mixed $value, array &$parts, ?string $key = null): void
    {
        if (is_string($value)) {
            if ($key !== null && in_array($key, self::SKIP_KEYS, true)) {
                return;
            }

            $trimmed = trim($value);

            if ($trimmed !== '' && (str_contains($trimmed, ' ') || mb_strlen($trimmed, 'UTF-8') > 15)) {
                $parts[] = $trimmed;
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $childKey => $child) {
                if (is_string($childKey) && in_array($childKey, self::SKIP_KEYS, true)) {
                    continue;
                }

                self::walk($child, $parts, is_string($childKey) ? $childKey : null);
            }
        }
    }
}