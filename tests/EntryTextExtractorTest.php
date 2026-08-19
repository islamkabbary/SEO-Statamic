<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Analysis\EntryTextExtractor;

final class EntryTextExtractorTest extends TestCase
{
    public function testExtractsSentenceLikeStringsAndCollapsesWhitespace(): void
    {
        $text = EntryTextExtractor::extract(['intro' => "This is   real body content."]);

        self::assertSame('This is real body content.', $text);
    }

    public function testSkipsSeoAndStructuralKeys(): void
    {
        $text = EntryTextExtractor::extract([
            'seo_title' => 'ignored seo title',
            'seo_description' => 'ignored seo description',
            'seo_schema_json' => '{"@type":"Article"}',
            'slug' => 'ignored-slug-value-here',
            'id' => 'ignored-id-value-here',
            'content' => 'kept body paragraph text',
        ]);

        self::assertStringContainsString('kept body paragraph text', $text);
        self::assertStringNotContainsString('ignored', $text);
        self::assertStringNotContainsString('@type', $text);
    }

    public function testWalksNestedReplicatorStructures(): void
    {
        $text = EntryTextExtractor::extract([
            'sets' => [
                ['type' => 'text', 'body' => 'first nested paragraph'],
                ['type' => 'text', 'body' => 'second nested paragraph'],
            ],
        ]);

        self::assertStringContainsString('first nested paragraph', $text);
        self::assertStringContainsString('second nested paragraph', $text);
    }

    public function testDropsShortStructuralTokens(): void
    {
        $text = EntryTextExtractor::extract(['flag' => 'on', 'tag' => 'h2']);

        self::assertSame('', $text);
    }
}