<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Support;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Support\RedirectTarget;

final class RedirectTargetTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function loopingTargets(): array
    {
        return [
            'identical path' => ['/about', '/about'],
            'target missing leading slash' => ['about', '/about'],
            'target has trailing slash' => ['/about/', '/about'],
            'request has trailing slash' => ['/about', 'about/'],
            'both unslashed' => ['about', 'about'],
            'nested path' => ['/blog/hello', 'blog/hello'],
            'target carries a query string' => ['/about?ref=nav', '/about'],
            'target carries a fragment' => ['/about#top', '/about'],
            'empty target at the root' => ['', '/'],
            'absolute url back to the same path' => ['https://example.com/about', '/about'],
            'absolute url, differing case in host' => ['https://EXAMPLE.com/about', '/about'],
            'absolute url with trailing slash' => ['https://example.com/about/', 'about'],
        ];
    }

    /**
     * @dataProvider loopingTargets
     */
    public function test_it_detects_a_rule_that_points_at_its_own_source(string $to, string $requestPath): void
    {
        self::assertTrue(RedirectTarget::pointsAtSelf($to, $requestPath, 'example.com'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function safeTargets(): array
    {
        return [
            'different path' => ['/new-about', '/about'],
            'parent path' => ['/', '/about'],
            'child path' => ['/about/team', '/about'],
            'prefix only' => ['/about-us', '/about'],
            'empty target away from the root' => ['', '/about'],
            'absolute url to another host' => ['https://other.example/about', '/about'],
            'absolute url to another path on the same host' => ['https://example.com/new', '/about'],
            'protocol relative to another host' => ['//other.example/about', '/about'],
            'different scheme, same host and path' => ['http://example.com/new', '/about'],
        ];
    }

    /**
     * @dataProvider safeTargets
     */
    public function test_it_allows_a_rule_that_moves_the_request(string $to, string $requestPath): void
    {
        self::assertFalse(RedirectTarget::pointsAtSelf($to, $requestPath, 'example.com'));
    }

    public function test_an_absolute_target_is_allowed_when_the_host_is_unknown(): void
    {
        // Without a request host there is nothing to compare against; assuming a
        // loop would break legitimate cross-domain rules.
        self::assertFalse(RedirectTarget::pointsAtSelf('https://example.com/about', '/about'));
    }

    public function test_it_handles_arabic_paths(): void
    {
        // lara_proonline's redirect table stores un-encoded Arabic slugs.
        self::assertTrue(RedirectTarget::pointsAtSelf('/الأمن-السيبراني', 'الأمن-السيبراني', 'example.com'));
        self::assertFalse(RedirectTarget::pointsAtSelf('/الأمن', '/الأمن-السيبراني', 'example.com'));
    }
}
