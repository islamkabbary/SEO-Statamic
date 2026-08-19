<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\IndexNow\IndexNowSubmission;

final class IndexNowSubmissionTest extends TestCase
{
    public function testDerivesHostAndKeyLocation(): void
    {
        $target = IndexNowSubmission::for('https://silaeng.com/ar/blog/x', 'abc123def456');

        self::assertSame('silaeng.com', $target['host']);
        self::assertSame('https://silaeng.com/abc123def456.txt', $target['keyLocation']);
        self::assertSame('https://silaeng.com/ar/blog/x', $target['url']);
    }

    public function testDefaultsToHttpsWhenSchemeMissing(): void
    {
        $target = IndexNowSubmission::for('//silaeng.com/page', 'key12345');

        self::assertSame('https://silaeng.com/key12345.txt', $target['keyLocation']);
    }

    public function testReturnsNullWithoutHostOrKey(): void
    {
        self::assertNull(IndexNowSubmission::for('/relative/path', 'key12345'));
        self::assertNull(IndexNowSubmission::for('https://silaeng.com/x', ''));
        self::assertNull(IndexNowSubmission::for('', 'key12345'));
    }
}