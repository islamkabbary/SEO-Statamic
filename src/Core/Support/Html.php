<?php

declare(strict_types=1);

namespace SilaSeo\Core\Support;

/**
 * Minimal HTML helpers. The core package has no framework dependency, so it
 * cannot rely on Laravel's `e()` helper for escaping.
 */
final class Html
{
    /**
     * Escape a value for use inside a double-quoted HTML attribute.
     */
    public static function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Render an attribute list into a ` key="value"` string, skipping null values.
     *
     * @param array<string, string|null> $attributes
     */
    public static function attributes(array $attributes): string
    {
        $rendered = '';

        foreach ($attributes as $name => $value) {
            if ($value === null) {
                continue;
            }

            $rendered .= ' ' . $name . '="' . self::attr($value) . '"';
        }

        return $rendered;
    }
}