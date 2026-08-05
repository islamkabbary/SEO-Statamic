<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Text;

/**
 * Extracts structured content from a page body. Behind an interface so a future
 * DOM-based extractor can replace the regex one without touching checks.
 */
interface ContentParser
{
    public function parse(string $body, ?string $siteHost = null): ExtractedContent;
}