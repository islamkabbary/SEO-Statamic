<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Schema;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Schema\Types\Article;
use SilaSeo\Core\Schema\Types\Organization;
use SilaSeo\Core\Schema\Validator;

final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidOrganizationPasses(): void
    {
        self::assertTrue($this->validator->isValid(new Organization('Sila', 'https://example.com')));
    }

    public function testMissingRequiredPropertyIsReported(): void
    {
        $issues = $this->validator->validate(['@type' => 'Article']);

        self::assertCount(1, $issues);
        self::assertSame('headline', $issues[0]->property);
        self::assertTrue($issues[0]->isError());
    }

    public function testNodeWithoutTypeIsInvalid(): void
    {
        $issues = $this->validator->validate(['name' => 'No type']);

        self::assertCount(1, $issues);
        self::assertSame('@type', $issues[0]->property);
    }

    public function testValidArticlePasses(): void
    {
        $article = (new Article('A headline'))->author('Islam');

        self::assertTrue($this->validator->isValid($article));
    }
}