<?php

declare(strict_types=1);

namespace SilaSeo\Core\Link;

/**
 * A page that could be linked TO from the content being edited. Keyphrases are
 * the phrases whose presence in the content makes this target a relevant link
 * (typically the target's focus keyword plus its title).
 */
final class LinkTarget
{
    /**
     * @param list<string> $keyphrases Non-empty phrases, most relevant first.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $url,
        public readonly array $keyphrases,
        public readonly string $locale = '',
    ) {
    }
}