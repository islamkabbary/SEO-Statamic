<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema;

/**
 * Recursively strips null values, empty strings, and empty arrays from a node
 * so that JSON-LD never ships empty or null properties.
 */
final class Fallback
{
    /**
     * @param array<string,mixed> $node
     *
     * @return array<string,mixed>
     */
    public static function clean(array $node): array
    {
        $cleaned = [];

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $value = self::clean($value);

                if ($value === []) {
                    continue;
                }
            } elseif ($value === null || $value === '') {
                continue;
            }

            $cleaned[$key] = $value;
        }

        return self::reindexList($cleaned);
    }

    /**
     * Re-index arrays whose keys are all integers so json_encode renders them as
     * JSON arrays rather than objects after intermediate elements were removed.
     *
     * @param array<string,mixed> $node
     *
     * @return array<string,mixed>
     */
    private static function reindexList(array $node): array
    {
        if ($node !== [] && self::hasOnlyIntKeys($node)) {
            return array_values($node);
        }

        return $node;
    }

    /**
     * @param array<string,mixed> $node
     */
    private static function hasOnlyIntKeys(array $node): bool
    {
        foreach (array_keys($node) as $key) {
            if (! is_int($key)) {
                return false;
            }
        }

        return true;
    }
}