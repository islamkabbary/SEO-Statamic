<?php

declare(strict_types=1);

namespace SilaSeo\Core\Link;

/**
 * One target keyphrase found in the content, with how many times it occurs.
 */
final class PhraseMatch
{
    public function __construct(
        public readonly string $phrase,
        public readonly int $count,
    ) {
    }
}