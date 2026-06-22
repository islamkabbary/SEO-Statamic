<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Text;

/**
 * Structured content pulled out of a page body once, up front.
 */
final class ExtractedContent
{
    /**
     * @param list<array{level:int,text:string}> $headings
     * @param list<array{internal:bool,nofollow:bool}> $links
     * @param list<array{alt:?string}> $images
     */
    public function __construct(
        public readonly string $plainText,
        public readonly string $firstParagraph,
        public readonly array $headings,
        public readonly array $links,
        public readonly array $images,
    ) {
    }

    public function h1Count(): int
    {
        return count(array_filter($this->headings, static fn (array $h): bool => $h['level'] === 1));
    }

    /**
     * @return list<array{level:int,text:string}>
     */
    public function headingsOfLevel(int ...$levels): array
    {
        return array_values(array_filter(
            $this->headings,
            static fn (array $h): bool => in_array($h['level'], $levels, true),
        ));
    }

    public function internalLinkCount(): int
    {
        return count(array_filter($this->links, static fn (array $l): bool => $l['internal']));
    }

    public function externalLinkCount(): int
    {
        return count(array_filter($this->links, static fn (array $l): bool => ! $l['internal']));
    }

    public function imageCount(): int
    {
        return count($this->images);
    }

    public function imagesWithAltCount(): int
    {
        return count(array_filter(
            $this->images,
            static fn (array $i): bool => $i['alt'] !== null && trim($i['alt']) !== '',
        ));
    }
}