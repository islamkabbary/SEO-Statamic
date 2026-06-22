<?php

declare(strict_types=1);

namespace SilaSeo\Core\Link;

/**
 * The content being edited, against which internal-link targets are matched.
 * The text may be HTML or plain text; the locale drives Arabic normalization.
 */
final class LinkContent
{
    public function __construct(
        public readonly string $text,
        public readonly string $locale = 'en',
        public readonly string $excludeId = '',
        public readonly string $excludeUrl = '',
    ) {
    }

    public function isArabic(): bool
    {
        return strtolower(explode('-', str_replace('_', '-', $this->locale))[0]) === 'ar';
    }
}