<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Cascade;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Cascade\DefaultsResolver;

final class DefaultsResolverTest extends TestCase
{
    private DefaultsResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DefaultsResolver();
    }

    public function testLaterLayersOverrideEarlierOnes(): void
    {
        $resolved = $this->resolver->resolve(
            ['title' => 'Global', 'description' => 'Global desc'],
            ['title' => 'Entry'],
        );

        self::assertSame('Entry', $resolved['title']);
        self::assertSame('Global desc', $resolved['description']);
    }

    public function testNullInheritsThePreviousValue(): void
    {
        $resolved = $this->resolver->resolve(
            ['title' => 'Global'],
            ['title' => null],
        );

        self::assertSame('Global', $resolved['title']);
    }

    public function testEmptyStringExplicitlyClears(): void
    {
        $resolved = $this->resolver->resolve(
            ['title' => 'Global'],
            ['title' => ''],
        );

        self::assertSame('', $resolved['title']);
    }

    public function testSchemaIsAdditiveAcrossLayers(): void
    {
        $resolved = $this->resolver->resolve(
            ['schema' => [['@type' => 'Organization']]],
            ['schema' => [['@type' => 'Article']]],
        );

        self::assertCount(2, $resolved['schema']);
        self::assertSame('Organization', $resolved['schema'][0]['@type']);
        self::assertSame('Article', $resolved['schema'][1]['@type']);
    }
}