<?php

declare(strict_types=1);

namespace SilaSeo\Core\Link;

use SilaSeo\Core\Analysis\Text\KeywordMatcher;

/**
 * Suggests internal links for a piece of content: scans the content for the
 * keyphrases of other pages and ranks those pages by how prominently they are
 * mentioned. Pure PHP, locale-aware (Arabic-normalized) keyword matching.
 *
 * The content is tokenized once and reused across every candidate, so cost is
 * O(targets x phrases) sliding-window comparisons, not O(targets) re-parses.
 */
final class LinkSuggester
{
    public function __construct(private readonly int $maxSuggestions = 5)
    {
    }

    /**
     * @param iterable<LinkTarget> $targets
     *
     * @return list<LinkSuggestion>
     */
    public function suggest(LinkContent $content, iterable $targets): array
    {
        $matcher = new KeywordMatcher($content->isArabic());
        $haystack = $matcher->normalizedTokens($content->text);

        if ($haystack === []) {
            return [];
        }

        $suggestions = [];

        foreach ($targets as $target) {
            if (! $this->isCandidate($content, $target)) {
                continue;
            }

            $matches = $this->matchPhrases($matcher, $haystack, $target->keyphrases);

            if ($matches === []) {
                continue;
            }

            $score = array_sum(array_map(static fn (PhraseMatch $m): int => $m->count, $matches));
            $suggestions[] = new LinkSuggestion($target, $score, $matches);
        }

        usort(
            $suggestions,
            static fn (LinkSuggestion $a, LinkSuggestion $b): int => $b->score <=> $a->score
                ?: strcmp($a->target->title, $b->target->title),
        );

        return array_slice($this->dedupeByUrl($suggestions), 0, $this->maxSuggestions);
    }

    private function isCandidate(LinkContent $content, LinkTarget $target): bool
    {
        if ($target->id !== '' && $target->id === $content->excludeId) {
            return false;
        }

        if ($target->url === '' || $target->url === $content->excludeUrl) {
            return false;
        }

        // Only link within the same locale when both sides declare one.
        return $content->locale === '' || $target->locale === '' || $target->locale === $content->locale;
    }

    /**
     * Collapse targets that resolve to the same URL (e.g. localizations sharing a
     * permalink), keeping the highest-scoring one. Expects $suggestions sorted.
     *
     * @param list<LinkSuggestion> $suggestions
     *
     * @return list<LinkSuggestion>
     */
    private function dedupeByUrl(array $suggestions): array
    {
        $seen = [];
        $unique = [];

        foreach ($suggestions as $suggestion) {
            if (isset($seen[$suggestion->target->url])) {
                continue;
            }

            $seen[$suggestion->target->url] = true;
            $unique[] = $suggestion;
        }

        return $unique;
    }

    /**
     * @param list<string> $haystack
     * @param list<string> $keyphrases
     *
     * @return list<PhraseMatch>
     */
    private function matchPhrases(KeywordMatcher $matcher, array $haystack, array $keyphrases): array
    {
        $matches = [];
        $seen = [];

        foreach ($keyphrases as $phrase) {
            $phrase = trim($phrase);

            if ($phrase === '' || isset($seen[mb_strtolower($phrase, 'UTF-8')])) {
                continue;
            }

            $seen[mb_strtolower($phrase, 'UTF-8')] = true;
            $count = $matcher->countIn($haystack, $phrase);

            if ($count > 0) {
                $matches[] = new PhraseMatch($phrase, $count);
            }
        }

        usort($matches, static fn (PhraseMatch $a, PhraseMatch $b): int => $b->count <=> $a->count);

        return $matches;
    }
}