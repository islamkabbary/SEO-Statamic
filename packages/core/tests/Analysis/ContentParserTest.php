<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Analysis;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Analysis\Text\RegexContentParser;

final class ContentParserTest extends TestCase
{
    private const HTML = '<h1>Main Title</h1><p>First paragraph text.</p><h2>Sub</h2>'
        . '<a href="/about">internal</a><a href="https://ext.com/x">external</a>'
        . '<img src="a.jpg" alt="cat"><img src="b.jpg">';

    public function testExtractsHeadings(): void
    {
        $content = (new RegexContentParser())->parse(self::HTML);

        self::assertSame(1, $content->h1Count());
        self::assertCount(1, $content->headingsOfLevel(2));
        self::assertSame('Main Title', $content->headings[0]['text']);
    }

    public function testClassifiesLinksByHost(): void
    {
        $parser = new RegexContentParser();

        $content = $parser->parse(self::HTML);
        self::assertSame(1, $content->internalLinkCount());
        self::assertSame(1, $content->externalLinkCount());

        // When the external host matches the site host it becomes internal.
        $sameHost = $parser->parse(self::HTML, 'ext.com');
        self::assertSame(2, $sameHost->internalLinkCount());
    }

    public function testCountsImageAltCoverage(): void
    {
        $content = (new RegexContentParser())->parse(self::HTML);

        self::assertSame(2, $content->imageCount());
        self::assertSame(1, $content->imagesWithAltCount());
    }

    public function testExtractsFirstParagraphAndPlainText(): void
    {
        $content = (new RegexContentParser())->parse(self::HTML);

        self::assertSame('First paragraph text.', $content->firstParagraph);
        self::assertStringContainsString('Main Title', $content->plainText);
        self::assertStringContainsString('Sub', $content->plainText);
        self::assertStringNotContainsString('<', $content->plainText);
    }

    public function testHandlesPlainTextBody(): void
    {
        $content = (new RegexContentParser())->parse('Just some plain text without tags.');

        self::assertSame([], $content->headings);
        self::assertSame([], $content->links);
        self::assertStringContainsString('plain text', $content->plainText);
    }
}