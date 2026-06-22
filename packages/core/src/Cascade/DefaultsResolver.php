<?php

declare(strict_types=1);

namespace SilaSeo\Core\Cascade;

/**
 * Merges SEO payload layers into one resolved payload.
 *
 * Layers are passed least-specific first (package defaults → global →
 * collection/type → entry/model/route → runtime override); later layers win.
 *
 * Per-field semantics:
 *  - key absent or value `null`  → inherit the previous layer's value
 *  - value `''` (empty string)   → explicit clear (overrides an inherited value)
 *  - any other value             → override
 *
 * The `schema` key is additive: nodes from every layer are concatenated rather
 * than replaced, so collection-level and entry-level schema both contribute.
 */
final class DefaultsResolver
{
    /**
     * @param array<string,mixed> ...$layers
     *
     * @return array<string,mixed>
     */
    public function resolve(array ...$layers): array
    {
        $resolved = [];

        foreach ($layers as $layer) {
            foreach ($layer as $key => $value) {
                if ($key === 'schema') {
                    $resolved['schema'] = [
                        ...($resolved['schema'] ?? []),
                        ...(is_array($value) ? $value : []),
                    ];

                    continue;
                }

                if ($value === null) {
                    continue;
                }

                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }
}