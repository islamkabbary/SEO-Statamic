<?php

declare(strict_types=1);

namespace SilaSeo\Core\Meta;

/**
 * Resolves `%token%` placeholders inside a template string from a variables map.
 * Used for title patterns and schema field mapping (e.g. "%title% %sep% %sitename%").
 */
final class FieldMapper
{
    /**
     * @param array<string,string|null> $variables
     */
    public function map(string $template, array $variables): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements['%' . $key . '%'] = $value ?? '';
        }

        $result = strtr($template, $replacements);

        return $this->collapseWhitespace($result);
    }

    /**
     * Return the first non-empty value, or null when none qualifies.
     */
    public function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function collapseWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}