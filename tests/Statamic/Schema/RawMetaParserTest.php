<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Schema;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Schema\RawMetaParser;

/**
 * The freeform "meta tags" field older projects use. Its contents are whatever an
 * editor pasted, so the parser's job is to extract what it can and discard the
 * rest without ever throwing.
 */
final class RawMetaParserTest extends TestCase
{
    private function parse(?string $raw): array
    {
        return (new RawMetaParser())->parse($raw);
    }

    public function test_it_extracts_a_json_ld_block(): void
    {
        $result = $this->parse('<script type="application/ld+json">{"@context":"https://schema.org","@type":"Course","name":"IELTS"}</script>');

        self::assertCount(1, $result['schema']);
        self::assertSame('Course', $result['schema'][0]['@type']);
        self::assertSame('IELTS', $result['schema'][0]['name']);
    }

    public function test_it_extracts_several_json_ld_blocks(): void
    {
        $result = $this->parse(
            '<script type="application/ld+json">{"@type":"Course","name":"A"}</script>'
            . '<script type="application/ld+json">{"@type":"FAQPage","name":"B"}</script>',
        );

        self::assertSame(['Course', 'FAQPage'], array_column($result['schema'], '@type'));
    }

    public function test_it_unwraps_a_graph(): void
    {
        $result = $this->parse('<script type="application/ld+json">{"@graph":[{"@type":"Course"},{"@type":"Organization"}]}</script>');

        self::assertSame(['Course', 'Organization'], array_column($result['schema'], '@type'));
    }

    public function test_it_accepts_bare_json_with_no_script_wrapper(): void
    {
        $result = $this->parse('{"@type":"Article","headline":"Hello"}');

        self::assertSame('Article', $result['schema'][0]['@type']);
    }

    public function test_it_decodes_entity_encoded_markup(): void
    {
        // Fields that HTML-escape on save store the block like this.
        $result = $this->parse('&lt;script type="application/ld+json"&gt;{"@type":"Course"}&lt;/script&gt;');

        self::assertSame('Course', $result['schema'][0]['@type']);
    }

    public function test_it_collapses_a_duplicated_block(): void
    {
        $block = '<script type="application/ld+json">{"@type":"Course","name":"A"}</script>';

        self::assertCount(1, $this->parse($block . $block)['schema']);
    }

    public function test_it_keeps_distinct_nodes_of_the_same_type(): void
    {
        $result = $this->parse(
            '<script type="application/ld+json">{"@type":"Course","name":"A"}</script>'
            . '<script type="application/ld+json">{"@type":"Course","name":"B"}</script>',
        );

        self::assertCount(2, $result['schema']);
    }

    public function test_it_drops_a_node_without_a_type(): void
    {
        $result = $this->parse('<script type="application/ld+json">{"name":"No type"}</script>');

        self::assertSame([], $result['schema']);
    }

    public function test_it_extracts_meta_tags(): void
    {
        $result = $this->parse('<meta name="description" content="Hello"><meta property="og:title" content="Title">');

        self::assertSame([
            ['key' => 'description', 'content' => 'Hello', 'property' => false],
            ['key' => 'og:title', 'content' => 'Title', 'property' => true],
        ], $result['meta']);
    }

    public function test_it_keeps_the_first_of_a_duplicated_meta_tag(): void
    {
        $result = $this->parse('<meta property="og:title" content="First"><meta property="og:title" content="Second">');

        self::assertCount(1, $result['meta']);
        self::assertSame('First', $result['meta'][0]['content']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableTags(): array
    {
        return [
            'no content attribute' => ['<meta name="description">'],
            'blank content' => ['<meta name="description" content="   ">'],
            'no key' => ['<meta content="orphaned">'],
            'charset carries no key' => ['<meta charset="utf-8">'],
        ];
    }

    /**
     * @dataProvider unusableTags
     */
    public function test_it_drops_a_tag_that_carries_nothing(string $markup): void
    {
        self::assertSame([], $this->parse($markup)['meta']);
    }

    public function test_it_handles_single_quoted_and_unquoted_attributes(): void
    {
        $result = $this->parse("<meta name='description' content='Hello'><meta name=keywords content=one>");

        self::assertSame(['description', 'keywords'], array_column($result['meta'], 'key'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformed(): array
    {
        return [
            'unterminated script' => ['<script type="application/ld+json">{"@type":"Course"'],
            'invalid json' => ['<script type="application/ld+json">{ not json at all }</script>'],
            'unclosed meta' => ['<meta name="description" content="Hello"'],
            'stray angle brackets' => ['< > <<>> </>'],
            'nested quotes' => ['<meta name="a" content="he said "hi"">'],
            'html soup' => ['<div><p>text<span></div>'],
            'null bytes' => ["<meta name=\"a\" content=\"b\"\0"],
            'very deep json' => ['<script type="application/ld+json">' . str_repeat('[', 200) . str_repeat(']', 200) . '</script>'],
        ];
    }

    /**
     * @dataProvider malformed
     */
    public function test_it_never_throws_on_malformed_input(string $markup): void
    {
        $result = $this->parse($markup);

        self::assertIsArray($result['schema']);
        self::assertIsArray($result['meta']);
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function nothing(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace' => ["  \n\t "],
        ];
    }

    /**
     * @dataProvider nothing
     */
    public function test_an_absent_field_yields_nothing(?string $raw): void
    {
        self::assertSame(['schema' => [], 'meta' => [], 'keywords' => null], $this->parse($raw));
    }

    public function test_it_reports_leftover_text_separately_rather_than_emitting_it(): void
    {
        // One real entry holds a bare comma-separated Arabic keyword list, which is
        // today echoed straight into <head> as loose text between the tags.
        $result = $this->parse('دورات, تعليم, لغة إنجليزية');

        self::assertSame([], $result['schema']);
        self::assertSame([], $result['meta']);
        self::assertSame('دورات, تعليم, لغة إنجليزية', $result['keywords']);
    }

    public function test_json_ld_content_is_not_reported_as_leftover_text(): void
    {
        $result = $this->parse('<script type="application/ld+json">{"@type":"Course","name":"IELTS"}</script>');

        self::assertNull($result['keywords']);
    }

    public function test_it_handles_a_mixture_of_everything(): void
    {
        $result = $this->parse(
            '<meta name="description" content="Desc">'
            . '<script type="application/ld+json">{"@type":"Course","name":"A"}</script>'
            . '<meta property="og:image" content="https://example.com/a.jpg">'
            . 'trailing words',
        );

        self::assertSame(['Course'], array_column($result['schema'], '@type'));
        self::assertSame(['description', 'og:image'], array_column($result['meta'], 'key'));
        self::assertSame('trailing words', $result['keywords']);
    }
}
