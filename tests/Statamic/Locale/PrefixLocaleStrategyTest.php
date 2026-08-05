<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Locale;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Fields\FieldMap;
use SilaSeo\Statamic\Locale\PrefixLocaleStrategy;
use SilaSeo\Statamic\Tests\Doubles\FakeEntry;
use SilaSeo\Statamic\Tests\Doubles\FakeReader;

/**
 * One site, two languages by URL prefix, content in `_ar` twins.
 */
final class PrefixLocaleStrategyTest extends TestCase
{
    /**
     * @return array<string, array{prefix?: string, hreflang?: string, x_default?: bool}>
     */
    private function locales(): array
    {
        return [
            'ar' => ['prefix' => '', 'hreflang' => 'ar', 'x_default' => true],
            'en' => ['prefix' => 'en', 'hreflang' => 'en'],
        ];
    }

    private function strategy(?string $url): PrefixLocaleStrategy
    {
        return new PrefixLocaleStrategy($this->locales(), new FakeReader(), $url);
    }

    private function map(): FieldMap
    {
        return FieldMap::fromArray([
            'locale_strategy' => 'prefix',
            'suffixes' => ['ar' => '_ar', 'en' => ''],
            'fields' => ['title' => 'seo_title', 'description' => 'description'],
            'fallbacks' => ['title' => ['title']],
        ]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function urls(): array
    {
        return [
            'root is the unprefixed locale' => ['https://example.com/', 'ar'],
            'no trailing slash' => ['https://example.com', 'ar'],
            'unprefixed nested path' => ['https://example.com/blog/post', 'ar'],
            'prefixed root' => ['https://example.com/en', 'en'],
            'prefixed root with slash' => ['https://example.com/en/', 'en'],
            'prefixed nested path' => ['https://example.com/en/blog/post', 'en'],
            'a path merely starting with the letters' => ['https://example.com/english-courses', 'ar'],
            'arabic slug' => ['https://example.com/blog/الأمن-السيبراني', 'ar'],
        ];
    }

    /**
     * @dataProvider urls
     */
    public function test_it_reads_the_locale_from_the_url(string $url, string $expected): void
    {
        // Deliberately from the URL, never the site config: two projects mutate the
        // site's locale from $_SESSION inside config/statamic/sites.php, which
        // config:cache then freezes.
        self::assertSame($expected, $this->strategy($url)->current());
    }

    public function test_it_emits_an_alternate_for_each_translated_locale(): void
    {
        $entry = new FakeEntry(['seo_title' => 'English Title', 'seo_title_ar' => 'عنوان']);

        $alternates = $this->strategy('https://example.com/blog/post')->alternatesFor($entry, $this->map());

        self::assertSame([
            ['hreflang' => 'ar', 'url' => 'https://example.com/blog/post'],
            ['hreflang' => 'x-default', 'url' => 'https://example.com/blog/post'],
            ['hreflang' => 'en', 'url' => 'https://example.com/en/blog/post'],
        ], $alternates);
    }

    public function test_it_builds_the_same_alternates_from_either_prefix(): void
    {
        $entry = new FakeEntry(['seo_title' => 'English Title', 'seo_title_ar' => 'عنوان']);
        $map = $this->map();

        // The canonical path is recovered by stripping the current prefix, so the
        // set of alternates must not depend on which one the visitor arrived on.
        self::assertSame(
            $this->strategy('https://example.com/blog/post')->alternatesFor($entry, $map),
            $this->strategy('https://example.com/en/blog/post')->alternatesFor($entry, $map),
        );
    }

    public function test_it_does_not_advertise_a_locale_the_entry_was_never_translated_into(): void
    {
        // Only Arabic exists. Advertising /en/blog/post tells a crawler there is an
        // English page; the visitor would get Arabic.
        $entry = new FakeEntry(['seo_title_ar' => 'عنوان']);

        self::assertSame([], $this->strategy('https://example.com/blog/post')->alternatesFor($entry, $this->map()));
    }

    public function test_it_does_not_advertise_a_locale_whose_title_is_blank(): void
    {
        $entry = new FakeEntry(['seo_title' => '   ', 'seo_title_ar' => 'عنوان']);

        self::assertSame([], $this->strategy('https://example.com/blog/post')->alternatesFor($entry, $this->map()));
    }

    public function test_a_translated_fallback_handle_counts_as_existing(): void
    {
        // The entry has no seo_title_ar but does have title_ar -- the Arabic page
        // exists, it just has no SEO override.
        $entry = new FakeEntry(['seo_title' => 'English', 'title_ar' => 'عنوان']);

        $alternates = $this->strategy('https://example.com/p')->alternatesFor($entry, $this->map());

        self::assertSame(['ar', 'x-default', 'en'], array_column($alternates, 'hreflang'));
    }

    public function test_a_single_configured_locale_emits_nothing(): void
    {
        $strategy = new PrefixLocaleStrategy(
            ['ar' => ['prefix' => '', 'hreflang' => 'ar']],
            new FakeReader(),
            'https://example.com/p',
        );

        self::assertSame([], $strategy->alternatesFor(new FakeEntry(['seo_title' => 'x']), $this->map()));
    }

    public function test_an_unusable_hreflang_is_dropped_rather_than_emitted(): void
    {
        $strategy = new PrefixLocaleStrategy(
            [
                'ar' => ['prefix' => '', 'hreflang' => 'ar'],
                'default' => ['prefix' => 'x', 'hreflang' => 'default'],
            ],
            new FakeReader(),
            'https://example.com/p',
        );

        $entry = new FakeEntry(['seo_title' => 'x', 'seo_title_ar' => 'ع']);

        // One usable alternate is not a set of alternates.
        self::assertSame([], $strategy->alternatesFor($entry, $this->map()));
    }

    public function test_the_root_page_gets_clean_alternate_urls(): void
    {
        $entry = new FakeEntry(['seo_title' => 'Home', 'seo_title_ar' => 'الرئيسية']);

        $alternates = $this->strategy('https://example.com/')->alternatesFor($entry, $this->map());

        self::assertSame('https://example.com/', $alternates[0]['url']);
        self::assertSame('https://example.com/en', $alternates[2]['url']);
    }

    public function test_it_preserves_a_non_standard_port(): void
    {
        $entry = new FakeEntry(['seo_title' => 'x', 'seo_title_ar' => 'ع']);

        $alternates = $this->strategy('http://localhost:8000/blog/post')->alternatesFor($entry, $this->map());

        self::assertSame('http://localhost:8000/blog/post', $alternates[0]['url']);
    }

    public function test_it_reports_its_locales_and_name(): void
    {
        self::assertSame('prefix', $this->strategy('/')->name());
        self::assertSame(['ar', 'en'], $this->strategy('/')->locales());
    }
}
