<?php

declare(strict_types=1);

namespace SilaSeo\Core\Meta;

/**
 * Builds the final document title from a page title, an optional pattern, and
 * the site name. The pattern may use %title%, %sitename% and %sep% tokens; when
 * no pattern is given the title falls back to "Title - Site Name".
 */
final class TitleFormatter
{
    private const DEFAULT_SEPARATOR = '-';

    public function __construct(
        private readonly FieldMapper $mapper = new FieldMapper(),
    ) {
    }

    public function format(
        ?string $title,
        ?string $pattern = null,
        ?string $siteName = null,
        string $separator = self::DEFAULT_SEPARATOR,
    ): string {
        $title = $this->trimToNull($title);
        $siteName = $this->trimToNull($siteName);

        if ($title === null) {
            return $siteName ?? '';
        }

        if ($pattern !== null && trim($pattern) !== '') {
            return $this->mapper->map($pattern, [
                'title' => $title,
                'sitename' => $siteName,
                'sep' => $separator,
            ]);
        }

        if ($siteName === null) {
            return $title;
        }

        return $this->mapper->map('%title% %sep% %sitename%', [
            'title' => $title,
            'sitename' => $siteName,
            'sep' => $separator,
        ]);
    }

    private function trimToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}