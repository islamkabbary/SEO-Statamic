<?php

declare(strict_types=1);

namespace SilaSeo\Core\Hreflang;

/**
 * A single language/region alternate for a page, rendered as
 * <link rel="alternate" hreflang="..." href="...">.
 */
final class Alternate
{
    public function __construct(
        public readonly string $hreflang,
        public readonly string $url,
    ) {
    }

    public function isXDefault(): bool
    {
        return strtolower($this->hreflang) === 'x-default';
    }
}