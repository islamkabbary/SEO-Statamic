<?php

declare(strict_types=1);

namespace SilaSeo\Core\Link;

/**
 * A suggested internal link: a target the content already mentions, ranked by
 * how prominently its keyphrases appear.
 */
final class LinkSuggestion
{
    /**
     * @param list<PhraseMatch> $matches Phrases of the target found in the content, score-ordered.
     */
    public function __construct(
        public readonly LinkTarget $target,
        public readonly int $score,
        public readonly array $matches,
    ) {
    }

    /**
     * @return array{id: string, title: string, url: string, score: int, matches: list<array{phrase: string, count: int}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->target->id,
            'title' => $this->target->title,
            'url' => $this->target->url,
            'score' => $this->score,
            'matches' => array_map(
                static fn (PhraseMatch $m): array => ['phrase' => $m->phrase, 'count' => $m->count],
                $this->matches,
            ),
        ];
    }
}