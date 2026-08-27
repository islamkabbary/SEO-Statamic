<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Link;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Link\LinkContent;
use SilaSeo\Core\Link\LinkSuggester;
use SilaSeo\Core\Link\LinkTarget;

final class LinkSuggesterTest extends TestCase
{
    public function testSuggestsTargetsMentionedInContent(): void
    {
        $content = new LinkContent(
            'We design sustainable buildings and pursue green architecture in every project.',
            'en',
            'current',
        );

        $targets = [
            new LinkTarget('a', 'Sustainable Buildings', '/en/sustainable-buildings', ['sustainable buildings'], 'en'),
            new LinkTarget('b', 'Office Furniture', '/en/office-furniture', ['office furniture'], 'en'),
        ];

        $suggestions = (new LinkSuggester())->suggest($content, $targets);

        self::assertCount(1, $suggestions);
        self::assertSame('a', $suggestions[0]->target->id);
        self::assertSame(1, $suggestions[0]->score);
        self::assertSame('sustainable buildings', $suggestions[0]->matches[0]->phrase);
    }

    public function testExcludesSelfAndUrllessTargets(): void
    {
        $content = new LinkContent('Sustainable design drives sustainable growth.', 'en', 'self');

        $targets = [
            new LinkTarget('self', 'Self', '/en/self', ['sustainable'], 'en'),
            new LinkTarget('no-url', 'No URL', '', ['sustainable'], 'en'),
            new LinkTarget('other', 'Other', '/en/other', ['sustainable'], 'en'),
        ];

        $suggestions = (new LinkSuggester())->suggest($content, $targets);

        self::assertCount(1, $suggestions);
        self::assertSame('other', $suggestions[0]->target->id);
        self::assertSame(2, $suggestions[0]->score);
    }

    public function testFiltersByLocaleWhenBothDeclareOne(): void
    {
        $content = new LinkContent('Sustainable buildings everywhere.', 'en', 'current');

        $targets = [
            new LinkTarget('ar', 'Arabic', '/ar/x', ['sustainable buildings'], 'ar'),
            new LinkTarget('en', 'English', '/en/x', ['sustainable buildings'], 'en'),
            new LinkTarget('any', 'No locale', '/x', ['sustainable buildings'], ''),
        ];

        $ids = array_map(
            static fn ($s): string => $s->target->id,
            (new LinkSuggester())->suggest($content, $targets),
        );

        self::assertContains('en', $ids);
        self::assertContains('any', $ids);
        self::assertNotContains('ar', $ids);
    }

    public function testRanksByScoreAndHonoursLimit(): void
    {
        $content = new LinkContent('green green green roof tiles steel', 'en', 'current');

        $targets = [
            new LinkTarget('green', 'Green', '/en/green', ['green'], 'en'),
            new LinkTarget('roof', 'Roof', '/en/roof', ['roof'], 'en'),
            new LinkTarget('steel', 'Steel', '/en/steel', ['steel'], 'en'),
        ];

        $suggestions = (new LinkSuggester(2))->suggest($content, $targets);

        self::assertCount(2, $suggestions);
        self::assertSame('green', $suggestions[0]->target->id);
        self::assertSame(3, $suggestions[0]->score);
    }

    public function testMatchesArabicAcrossArticleAndVariants(): void
    {
        $content = new LinkContent('نقدم خدمات المباني المستدامة في كل مشروع.', 'ar', 'current');

        $targets = [
            new LinkTarget('x', 'المباني المستدامة', '/ar/mabany', ['مباني مستدامة'], 'ar'),
        ];

        $suggestions = (new LinkSuggester())->suggest($content, $targets);

        self::assertCount(1, $suggestions);
        self::assertSame('x', $suggestions[0]->target->id);
    }

    public function testEmptyContentYieldsNoSuggestions(): void
    {
        $content = new LinkContent('', 'en', 'current');
        $targets = [new LinkTarget('a', 'A', '/en/a', ['anything'], 'en')];

        self::assertSame([], (new LinkSuggester())->suggest($content, $targets));
    }

    public function testExcludesTargetsSharingTheCurrentUrl(): void
    {
        $content = new LinkContent('Sustainable buildings everywhere.', 'en', 'self', '/en/self');

        $targets = [
            new LinkTarget('twin', 'Self EN twin', '/en/self', ['sustainable buildings'], 'en'),
            new LinkTarget('other', 'Other', '/en/other', ['sustainable buildings'], 'en'),
        ];

        $suggestions = (new LinkSuggester())->suggest($content, $targets);

        self::assertCount(1, $suggestions);
        self::assertSame('other', $suggestions[0]->target->id);
    }

    public function testCollapsesTargetsResolvingToTheSameUrl(): void
    {
        $content = new LinkContent('Sustainable buildings and green design everywhere.', 'en', 'current');

        $targets = [
            new LinkTarget('ar-twin', 'المباني المستدامة', '/shared', ['green design'], 'en'),
            new LinkTarget('en-twin', 'Sustainable Buildings', '/shared', ['sustainable buildings'], 'en'),
        ];

        $suggestions = (new LinkSuggester())->suggest($content, $targets);

        self::assertCount(1, $suggestions);
        self::assertSame('/shared', $suggestions[0]->target->url);
    }
}