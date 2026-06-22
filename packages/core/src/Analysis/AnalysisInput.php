<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * Everything the analysis engine needs about a page being edited. The body may
 * be HTML or plain text; the locale drives the English vs Arabic strategy.
 */
final class AnalysisInput
{
    /**
     * @param list<string> $secondaryKeywords Reported informationally; scoring uses the primary focus keyword.
     */
    public function __construct(
        public readonly string $focusKeyword,
        public readonly string $title,
        public readonly string $description,
        public readonly string $slug,
        public readonly string $body,
        public readonly string $locale = 'en',
        public readonly string $pageType = 'article',
        public readonly array $secondaryKeywords = [],
        public readonly ?string $siteHost = null,
    ) {
    }

    public function isArabic(): bool
    {
        return strtolower(explode('-', str_replace('_', '-', $this->locale))[0]) === 'ar';
    }

    public function hasBody(): bool
    {
        return trim(strip_tags($this->body)) !== '';
    }
}