<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Schema;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Schema\Graph;
use SilaSeo\Core\Schema\Types\Article;
use SilaSeo\Core\Schema\Types\BreadcrumbList;
use SilaSeo\Core\Schema\Types\Organization;

final class GraphTest extends TestCase
{
    public function testWrapsNodesInSchemaOrgGraph(): void
    {
        $graph = Graph::fromNodes([
            (new Organization('Sila', 'https://example.com'))->withId('https://example.com/#org'),
        ]);

        $array = $graph->toArray();

        self::assertSame('https://schema.org', $array['@context']);
        self::assertCount(1, $array['@graph']);
        self::assertSame('Organization', $array['@graph'][0]['@type']);
    }

    public function testMergesNodesSharingAnId(): void
    {
        $graph = Graph::fromNodes([
            ['@type' => 'Organization', '@id' => '#org', 'name' => 'Sila'],
            ['@type' => 'Organization', '@id' => '#org', 'url' => 'https://example.com'],
        ]);

        $nodes = $graph->toArray()['@graph'];

        self::assertCount(1, $nodes);
        self::assertSame('Sila', $nodes[0]['name']);
        self::assertSame('https://example.com', $nodes[0]['url']);
    }

    public function testKeepsAnonymousNodesSeparate(): void
    {
        $graph = Graph::fromNodes([
            ['@type' => 'Article', 'headline' => 'A'],
            ['@type' => 'Article', 'headline' => 'B'],
        ]);

        self::assertCount(2, $graph->toArray()['@graph']);
    }

    public function testRenderPreservesArabicAndEscapesSlashes(): void
    {
        $graph = Graph::fromNodes([
            (new Article('مرحبا بالعالم'))->mainEntityOfPage('https://example.com/ar/post'),
        ]);

        $json = $graph->render();

        self::assertStringContainsString('مرحبا بالعالم', $json);
        self::assertStringContainsString('https:\/\/example.com\/ar\/post', $json);
        self::assertStringNotContainsString('</script>', $json);
    }

    public function testBreadcrumbPositionsAreSequential(): void
    {
        $crumbs = (new BreadcrumbList())
            ->add('Home', 'https://example.com')
            ->add('Blog', 'https://example.com/blog')
            ->toArray();

        self::assertSame(1, $crumbs['itemListElement'][0]['position']);
        self::assertSame(2, $crumbs['itemListElement'][1]['position']);
    }
}