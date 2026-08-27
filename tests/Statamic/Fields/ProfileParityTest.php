<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Fields;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Fields\ProfileFactory;
use SilaSeo\Statamic\Locale\MultisiteLocaleStrategy;
use SilaSeo\Statamic\Locale\PrefixLocaleStrategy;
use SilaSeo\Statamic\Locale\SingleSiteLocaleStrategy;
use SilaSeo\Statamic\Tests\Doubles\FakeEntry;
use SilaSeo\Statamic\Tests\Doubles\FakeReader;

/**
 * The gate for this phase: one piece of content must produce the same SEO meaning
 * whatever shape the project stores it in.
 *
 * Two entries below carry identical content. One uses the handles this package
 * ships; the other uses the handles an older project already had, with Arabic in
 * `_ar` twins. Every logical field must resolve to the same value from both.
 */
final class ProfileParityTest extends TestCase
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function config(string $profile, array $overrides = []): array
    {
        return array_merge([
            'profile' => $profile,
            'profiles' => [
                'native' => [
                    'locale_strategy' => 'multisite',
                    'fields' => [
                        'title' => 'seo_title',
                        'description' => 'seo_description',
                        'image' => 'seo_image',
                        'canonical' => 'seo_canonical',
                        'robots' => 'seo_noindex',
                        'focus_keyword' => 'seo_focus_keyword',
                    ],
                    'fallbacks' => ['title' => ['title']],
                ],
                'suffixed' => [
                    'locale_strategy' => 'prefix',
                    'suffixes' => ['ar' => '_ar', 'en' => ''],
                    'suffixable' => ['title', 'description'],
                    'fields' => [
                        'title' => 'seo_title',
                        'description' => 'description',
                        'image' => 'social_image',
                        'canonical' => 'canonical_link',
                        'robots' => null,
                        'focus_keyword' => null,
                    ],
                    'fallbacks' => ['title' => ['title']],
                    'legacy_meta' => 'meta_tags',
                ],
            ],
            'locales' => [
                'ar' => ['prefix' => '', 'hreflang' => 'ar', 'x_default' => true],
                'en' => ['prefix' => 'en', 'hreflang' => 'en'],
            ],
        ], $overrides);
    }

    private function nativeEntry(): FakeEntry
    {
        return new FakeEntry([
            'title' => 'Learn English',
            'seo_title' => 'Learn English Online',
            'seo_description' => 'Structured English courses.',
            'seo_canonical' => 'https://example.com/canonical',
            'seo_image' => 'https://example.com/social.jpg',
        ]);
    }

    private function legacyEntry(): FakeEntry
    {
        return new FakeEntry([
            'title' => 'Learn English',
            'seo_title' => 'Learn English Online',
            'description' => 'Structured English courses.',
            'canonical_link' => 'https://example.com/canonical',
            'social_image' => 'https://example.com/social.jpg',
            'meta_tags' => '<meta name="description" content="Legacy blob">',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function logicalFields(): array
    {
        return [
            'title' => ['title'],
            'description' => ['description'],
            'canonical' => ['canonical'],
            'image' => ['image'],
        ];
    }

    /**
     * @dataProvider logicalFields
     */
    public function test_the_same_content_resolves_identically_through_either_profile(string $key): void
    {
        $native = (new ProfileFactory($this->config('native')))->resolver(new FakeReader());
        $legacy = (new ProfileFactory($this->config('suffixed')))->resolver(new FakeReader());

        self::assertSame(
            $native->string($this->nativeEntry(), $key, 'en'),
            $legacy->string($this->legacyEntry(), $key, 'en'),
            "Logical field [{$key}] must mean the same thing in both profiles.",
        );
    }

    public function test_every_logical_field_actually_resolved(): void
    {
        // A parity assertion between two nulls proves nothing.
        $legacy = (new ProfileFactory($this->config('suffixed')))->resolver(new FakeReader());
        $entry = $this->legacyEntry();

        foreach (['title', 'description', 'canonical', 'image'] as $key) {
            self::assertNotNull($legacy->string($entry, $key, 'en'), "[{$key}] resolved to null.");
        }
    }

    public function test_the_arabic_reading_of_a_legacy_entry_uses_the_twins(): void
    {
        $entry = new FakeEntry([
            'seo_title' => 'English Title',
            'seo_title_ar' => 'عنوان عربي',
            'description' => 'English description.',
            'description_ar' => 'وصف عربي.',
        ]);

        $legacy = (new ProfileFactory($this->config('suffixed')))->resolver(new FakeReader());

        self::assertSame('عنوان عربي', $legacy->string($entry, 'title', 'ar'));
        self::assertSame('وصف عربي.', $legacy->string($entry, 'description', 'ar'));
        self::assertSame('English Title', $legacy->string($entry, 'title', 'en'));
        self::assertSame('English description.', $legacy->string($entry, 'description', 'en'));
    }

    public function test_the_legacy_blob_never_outranks_a_structured_field(): void
    {
        // meta_tags carries a description too. The mapped field must win; the blob
        // is only ever a gap-filler.
        $legacy = (new ProfileFactory($this->config('suffixed')))->resolver(new FakeReader());

        self::assertSame('Structured English courses.', $legacy->string($this->legacyEntry(), 'description', 'en'));
    }

    /**
     * @return array<string, array{string, class-string}>
     */
    public static function strategies(): array
    {
        return [
            'multisite profile builds the multisite strategy' => ['native', MultisiteLocaleStrategy::class],
            'prefix profile builds the prefix strategy' => ['suffixed', PrefixLocaleStrategy::class],
        ];
    }

    /**
     * @param class-string $expected
     *
     * @dataProvider strategies
     */
    public function test_the_profile_selects_its_locale_strategy(string $profile, string $expected): void
    {
        $strategy = (new ProfileFactory($this->config($profile)))->localeStrategy(new FakeReader());

        self::assertInstanceOf($expected, $strategy);
    }

    public function test_a_prefix_profile_with_too_few_locales_degrades_to_single_site(): void
    {
        // Emitting no hreflang is incomplete; emitting a wrong one misdirects a
        // crawler. The degraded path must be the safe one.
        $factory = new ProfileFactory($this->config('suffixed', ['locales' => ['ar' => ['prefix' => '']]]));

        self::assertInstanceOf(SingleSiteLocaleStrategy::class, $factory->localeStrategy(new FakeReader()));
        self::assertSame([], $factory->localeStrategy(new FakeReader())->alternatesFor(new FakeEntry(), $factory->map()));
    }

    public function test_an_unknown_profile_name_falls_back_to_native(): void
    {
        $factory = new ProfileFactory($this->config('does-not-exist'));

        self::assertSame(['seo_title', 'title'], $factory->map()->candidates('title', 'en'));
    }

    public function test_an_empty_config_yields_a_usable_map(): void
    {
        $factory = new ProfileFactory([]);

        self::assertSame([], $factory->map()->candidates('title', 'en'));
    }

    public function test_a_missing_strategy_defaults_to_multisite_so_existing_projects_keep_their_hreflang(): void
    {
        // The three live projects are real multisite. Defaulting a missing or
        // empty config to single-site would silently strip their alternates --
        // exactly the regression this phase must not introduce. MultisiteLocale-
        // Strategy is safe on a single-site install anyway: it returns no
        // alternates when the entry belongs to fewer than two sites.
        $factory = new ProfileFactory([]);

        self::assertInstanceOf(MultisiteLocaleStrategy::class, $factory->localeStrategy(new FakeReader()));
    }
}
