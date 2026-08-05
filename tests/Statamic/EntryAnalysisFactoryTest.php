<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Analysis\EntryAnalysisFactory;
use SilaSeo\Statamic\Analysis\EntryTextExtractor;

final class EntryAnalysisFactoryTest extends TestCase
{
    public function testExtractsBodyTextAndSkipsSeoAndStructuralKeys(): void
    {
        $text = EntryTextExtractor::extract([
            'id' => 'abc-123',
            'blueprint' => 'blog',
            'seo_title' => 'Should be skipped',
            'title' => 'Coffee Brewing Guide',
            'content' => [
                ['type' => 'paragraph', 'text' => 'Coffee brewing rewards patience and care.'],
            ],
        ]);

        self::assertStringContainsString('Coffee Brewing Guide', $text);
        self::assertStringContainsString('rewards patience', $text);
        self::assertStringNotContainsString('Should be skipped', $text);
        self::assertStringNotContainsString('paragraph', $text);
        self::assertStringNotContainsString('abc-123', $text);
    }

    public function testBuildsAnalysisInputFromValuesArray(): void
    {
        $input = (new EntryAnalysisFactory())->fromValues([
            'seo_focus_keyword' => 'coffee',
            'title' => 'Coffee',
            'seo_title' => 'The Best Coffee Guide',
            'description' => 'A page about coffee brewing at home.',
            'slug' => 'coffee-guide',
            'intro' => 'Coffee brewing is a rewarding craft for everyone.',
        ], 'ar', 'product');

        self::assertSame('coffee', $input->focusKeyword);
        self::assertSame('The Best Coffee Guide', $input->title);
        self::assertSame('A page about coffee brewing at home.', $input->description);
        self::assertSame('coffee-guide', $input->slug);
        self::assertSame('ar', $input->locale);
        self::assertSame('product', $input->pageType);
        self::assertStringContainsString('rewarding craft', $input->body);
    }

    public function testBuildsAnalysisInputFromEntry(): void
    {
        $entry = new class {
            public function value(string $handle): mixed
            {
                return [
                    'seo_focus_keyword' => 'coffee',
                    'seo_title' => 'The Best Coffee Brewing Guide',
                    'seo_description' => 'Learn coffee brewing at home.',
                    'title' => 'Coffee',
                ][$handle] ?? null;
            }

            public function data(): Collection
            {
                return new Collection([
                    'title' => 'Coffee',
                    'intro' => 'Coffee brewing is a rewarding craft for everyone.',
                ]);
            }

            public function slug(): string
            {
                return 'coffee-brewing-guide';
            }

            public function locale(): string
            {
                return 'en';
            }
        };

        $input = (new EntryAnalysisFactory())->fromEntry($entry);

        self::assertSame('coffee', $input->focusKeyword);
        self::assertSame('The Best Coffee Brewing Guide', $input->title);
        self::assertSame('Learn coffee brewing at home.', $input->description);
        self::assertSame('coffee-brewing-guide', $input->slug);
        self::assertSame('en', $input->locale);
        self::assertStringContainsString('rewarding craft', $input->body);
    }
}