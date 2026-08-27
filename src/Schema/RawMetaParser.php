<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Schema;

use Throwable;

/**
 * Reads a freeform "meta tags" field written before a project adopted structured
 * SEO fields.
 *
 * These fields hold whatever an editor pasted: a JSON-LD block, a handful of
 * <meta> tags, both, entity-encoded markup, or -- in at least one real entry -- a
 * bare comma-separated list of Arabic keywords. Today the whole blob is echoed
 * into <head> unescaped, which is how a keyword list ends up rendered as loose
 * text between the meta tags.
 *
 * Two rules govern its use, and they are the caller's to enforce:
 *
 *  - it is a FALLBACK. A structured field always wins; this only fills a gap.
 *  - it never fails. Malformed markup yields less output, never an exception, and
 *    never a partially-written tag.
 *
 * Parsing is deliberately regex-based rather than DOM-based: the input is a
 * fragment, frequently invalid, and DOMDocument's error handling around fragments
 * is a poor trade for what amounts to tag scraping.
 */
final class RawMetaParser
{
    private const LD_JSON = '#<script\b[^>]*type\s*=\s*["\']application/ld\+json["\'][^>]*>(.*?)</script\s*>#is';

    private const META_TAG = '#<meta\b([^>]*)>#i';

    private const ATTRIBUTE = '#([a-zA-Z][\w:.-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))#';

    /**
     * @return array{
     *     schema: list<array<string,mixed>>,
     *     meta: list<array{key: string, content: string, property: bool}>,
     *     keywords: string|null
     * }
     */
    public function parse(?string $raw): array
    {
        $empty = ['schema' => [], 'meta' => [], 'keywords' => null];

        if (! is_string($raw) || trim($raw) === '') {
            return $empty;
        }

        try {
            $markup = $this->decode($raw);

            $schema = $this->schema($markup);
            $meta = $this->meta($markup);
            $keywords = $this->keywords($markup);

            return ['schema' => $schema, 'meta' => $meta, 'keywords' => $keywords];
        } catch (Throwable) {
            return $empty;
        }
    }

    /**
     * Editors paste through fields that HTML-escape on save, so the stored value is
     * often `&lt;script&gt;...`. Decode only when there is no real markup already,
     * so a genuine `&lt;` inside a JSON string is left alone.
     */
    private function decode(string $raw): string
    {
        if (preg_match('#<\s*(script|meta)\b#i', $raw) === 1) {
            return $raw;
        }

        if (str_contains($raw, '&lt;')) {
            return html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $raw;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function schema(string $markup): array
    {
        $blocks = [];

        if (preg_match_all(self::LD_JSON, $markup, $matches) === false) {
            return [];
        }

        foreach ($matches[1] ?? [] as $block) {
            $blocks[] = (string) $block;
        }

        // A field with no <script> wrapper may still be bare JSON.
        if ($blocks === [] && preg_match('#^\s*[\[{]#', $markup) === 1) {
            $blocks[] = $markup;
        }

        $nodes = [];

        foreach ($blocks as $block) {
            foreach ($this->decodeJsonLd($block) as $node) {
                $nodes[] = $node;
            }
        }

        return $this->dedupe($nodes);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function decodeJsonLd(string $block): array
    {
        $block = trim($block);

        // Editors sometimes wrap the JSON in an HTML comment or CDATA guard.
        $block = (string) preg_replace('#^\s*(?://\s*)?<!\[CDATA\[|\]\]>\s*$|^\s*<!--|-->\s*$#s', '', $block);

        $decoded = json_decode(trim($block), true);

        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            $decoded = $decoded['@graph'];
        }

        $candidates = array_is_list($decoded) ? $decoded : [$decoded];
        $nodes = [];

        foreach ($candidates as $node) {
            // A node without @type is not usable structured data.
            if (is_array($node) && isset($node['@type'])) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param list<array<string,mixed>> $nodes
     *
     * @return list<array<string,mixed>>
     */
    private function dedupe(array $nodes): array
    {
        $seen = [];
        $unique = [];

        foreach ($nodes as $node) {
            // Duplicate blocks are common where an editor pasted twice. Keying on
            // @id when present, and the whole node otherwise, collapses them
            // without merging genuinely different nodes of the same type.
            $key = is_string($node['@id'] ?? null) && $node['@id'] !== ''
                ? '@id:' . $node['@id']
                : 'hash:' . hash('xxh128', (string) json_encode($node));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $node;
        }

        return $unique;
    }

    /**
     * @return list<array{key: string, content: string, property: bool}>
     */
    private function meta(string $markup): array
    {
        if (preg_match_all(self::META_TAG, $markup, $matches) === false) {
            return [];
        }

        $tags = [];
        $seen = [];

        foreach ($matches[1] ?? [] as $attributes) {
            $parsed = $this->attributes((string) $attributes);

            $content = $parsed['content'] ?? null;
            $property = $parsed['property'] ?? null;
            $name = $parsed['name'] ?? $parsed['http-equiv'] ?? null;
            $key = $property ?? $name;

            // charset/viewport carry no key; a tag with no content carries nothing.
            if ($key === null || $key === '' || $content === null || trim($content) === '') {
                continue;
            }

            // First occurrence wins: a duplicated og:title should emit once, and the
            // earlier one is what the editor saw when they stopped editing.
            $dedupeKey = strtolower($key);

            if (isset($seen[$dedupeKey])) {
                continue;
            }

            $seen[$dedupeKey] = true;

            $tags[] = [
                'key' => $key,
                'content' => trim($content),
                'property' => $property !== null,
            ];
        }

        return $tags;
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $attributes): array
    {
        if (preg_match_all(self::ATTRIBUTE, $attributes, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $parsed = [];

        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = '';

            foreach ([2, 3, 4] as $group) {
                if (isset($match[$group]) && $match[$group] !== '') {
                    $value = $match[$group];

                    break;
                }
            }

            $parsed[$name] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $parsed;
    }

    /**
     * Text left over once every tag is stripped.
     *
     * Returned separately rather than emitted, so the caller decides. The default
     * is to drop it: a bare keyword list is exactly the content that has no place
     * in <head>, and rendering it is the bug this replaces.
     */
    private function keywords(string $markup): ?string
    {
        $residue = (string) preg_replace(
            ['#<script\b[^>]*>.*?</script\s*>#is', '#<[^>]+>#s', '#<!--.*?-->#s'],
            ' ',
            $markup,
        );

        $residue = trim((string) preg_replace('/\s+/u', ' ', $residue));

        return $residue === '' ? null : $residue;
    }
}
