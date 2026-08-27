<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Text;

/**
 * Default {@see ContentParser}: pulls headings, links and images out of HTML via
 * bounded regex passes before flattening the rest to plain text. Works on plain
 * text too (no tags → empty structure, body becomes the plain text).
 */
final class RegexContentParser implements ContentParser
{
    public function parse(string $body, ?string $siteHost = null): ExtractedContent
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $body);

        return new ExtractedContent(
            plainText: $this->plainText($body),
            firstParagraph: $this->firstParagraph($body),
            headings: $this->headings($body),
            links: $this->links($body, $siteHost),
            images: $this->images($body),
        );
    }

    private function plainText(string $body): string
    {
        $text = (string) preg_replace('#<(/?)(p|div|br|li|h[1-6]|tr|section|article)\b[^>]*>#i', "\n", $body);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/[ \t]+/u', ' ', $text));
    }

    private function firstParagraph(string $body): string
    {
        if (preg_match('#<p\b[^>]*>(.*?)</p>#is', $body, $m) === 1) {
            $text = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim((string) preg_replace('/\s+/u', ' ', $text));

            if ($text !== '') {
                return $text;
            }
        }

        $words = Tokenizer::words($this->plainText($body));

        return implode(' ', array_slice($words, 0, 50));
    }

    /**
     * @return list<array{level:int,text:string}>
     */
    private function headings(string $body): array
    {
        preg_match_all('#<h([1-6])\b[^>]*>(.*?)</h\1>#is', $body, $matches, PREG_SET_ORDER);

        $headings = [];

        foreach ($matches as $match) {
            $headings[] = [
                'level' => (int) $match[1],
                'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            ];
        }

        return $headings;
    }

    /**
     * @return list<array{internal:bool,nofollow:bool}>
     */
    private function links(string $body, ?string $siteHost): array
    {
        preg_match_all('#<a\b([^>]*)>#i', $body, $matches);

        $links = [];

        foreach ($matches[1] as $attributes) {
            $href = $this->attribute($attributes, 'href');

            if ($href === null || $href === '' || $this->isNonPageLink($href)) {
                continue;
            }

            $rel = $this->attribute($attributes, 'rel') ?? '';

            $links[] = [
                'internal' => $this->isInternal($href, $siteHost),
                'nofollow' => stripos($rel, 'nofollow') !== false,
            ];
        }

        return $links;
    }

    /**
     * @return list<array{alt:?string}>
     */
    private function images(string $body): array
    {
        preg_match_all('#<img\b([^>]*)>#i', $body, $matches);

        $images = [];

        foreach ($matches[1] as $attributes) {
            $images[] = ['alt' => $this->attribute($attributes, 'alt')];
        }

        return $images;
    }

    private function attribute(string $attributes, string $name): ?string
    {
        if (preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attributes, $m) !== 1) {
            return null;
        }

        return $m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : ($m[4] ?? ''));
    }

    private function isNonPageLink(string $href): bool
    {
        return str_starts_with($href, '#')
            || (bool) preg_match('#^(mailto:|tel:|javascript:)#i', $href);
    }

    private function isInternal(string $href, ?string $siteHost): bool
    {
        if (preg_match('#^https?://#i', $href) !== 1) {
            return true;
        }

        if ($siteHost === null) {
            return false;
        }

        $host = parse_url($href, PHP_URL_HOST);

        return is_string($host) && strcasecmp($host, $siteHost) === 0;
    }
}