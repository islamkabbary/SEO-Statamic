<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\EntryMapper;

final class EntryMapperTest extends TestCase
{
    private EntryMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new EntryMapper();
    }

    public function testUsesSeoTitleAndBuildsArticleSchema(): void
    {
        $payload = $this->mapper->toPayload(
            ['seo_title' => 'Custom SEO', 'seo_description' => 'A summary'],
            ['fallback_title' => 'Entry Title', 'schema_type' => 'BlogPosting', 'url' => 'https://x/p', 'locale' => 'ar'],
        );

        self::assertSame('Custom SEO', $payload['title']);
        self::assertSame('article', $payload['og_type']);
        self::assertSame('BlogPosting', $payload['schema'][0]['@type']);
        self::assertSame('Custom SEO', $payload['schema'][0]['headline']);
        self::assertSame('ar', $payload['schema'][0]['inLanguage']);
        self::assertSame('https://x/p', $payload['schema'][0]['mainEntityOfPage']);
    }

    public function testFallsBackToEntryTitleWhenSeoTitleBlank(): void
    {
        $payload = $this->mapper->toPayload(['seo_title' => '   '], ['fallback_title' => 'Entry Title']);

        self::assertSame('Entry Title', $payload['title']);
    }

    public function testNoindexProducesRobots(): void
    {
        $payload = $this->mapper->toPayload(['seo_title' => 'T', 'noindex' => true], []);

        self::assertSame(['noindex', 'follow'], $payload['robots']);
    }

    public function testDropsEmptyCanonicalAndDescription(): void
    {
        $payload = $this->mapper->toPayload(
            ['seo_title' => 'T', 'seo_canonical' => '', 'seo_description' => null],
            [],
        );

        self::assertArrayNotHasKey('canonical', $payload);
        self::assertArrayNotHasKey('description', $payload);
    }

    public function testImageFallsBackToContext(): void
    {
        $payload = $this->mapper->toPayload(['seo_title' => 'T'], ['fallback_image' => 'https://x/og.jpg']);

        self::assertSame('https://x/og.jpg', $payload['image']);
    }

    public function testRawSchemaJsonOverridesAutoNode(): void
    {
        $payload = $this->mapper->toPayload(
            ['seo_title' => 'T', 'schema_json' => '{"@type":"FAQPage","mainEntity":[]}'],
            ['schema_type' => 'BlogPosting'],
        );

        self::assertCount(1, $payload['schema']);
        self::assertSame('FAQPage', $payload['schema'][0]['@type']);
    }

    public function testRawSchemaJsonAcceptsGraphWrapperAndArray(): void
    {
        $graph = $this->mapper->toPayload(
            ['schema_json' => '{"@graph":[{"@type":"Product","name":"A"},{"@type":"Offer","price":"1"}]}'],
            [],
        );
        self::assertCount(2, $graph['schema']);

        $list = $this->mapper->toPayload(['schema_json' => '[{"@type":"Event","name":"E"}]'], []);
        self::assertSame('Event', $list['schema'][0]['@type']);
    }

    public function testInvalidRawSchemaJsonIsIgnoredAndAutoNodeKept(): void
    {
        $payload = $this->mapper->toPayload(
            ['seo_title' => 'T', 'schema_json' => 'not json {'],
            ['schema_type' => 'Article'],
        );

        self::assertCount(1, $payload['schema']);
        self::assertSame('Article', $payload['schema'][0]['@type']);
    }
}