<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Gateway;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Fields\ProfileFactory;
use SilaSeo\Statamic\Gateway\VersionGate;
use SilaSeo\Statamic\Tests\Doubles\FakeEntry;
use SilaSeo\Statamic\Tests\Doubles\FakeReader;

/**
 * The phase gate: the payload the gateway hands the meta builder must carry the
 * same meaning for the same content, whichever shape the project stores it in.
 *
 * This is the end-to-end version of the resolver-level parity test -- it exercises
 * the object the rest of the package actually consumes.
 */
final class GatewayParityTest extends TestCase
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function config(string $profile, array $overrides = []): array
    {
        return [...[
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
                        'schema_type' => 'seo_schema_type',
                        'schema_json' => 'seo_schema_json',
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
                        'schema_type' => null,
                        'schema_json' => null,
                    ],
                    'fallbacks' => ['title' => ['title']],
                    'legacy_meta' => 'meta_tags',
                ],
            ],
            'locales' => [
                'ar' => ['prefix' => '', 'hreflang' => 'ar', 'x_default' => true],
                'en' => ['prefix' => 'en', 'hreflang' => 'en'],
            ],
        ], ...[$overrides]];
    }

    /**
     * @return array<string, mixed>
     */
    private function extract(string $profile, FakeEntry $entry): array
    {
        $factory = new ProfileFactory($this->config($profile));
        $reader = new FakeReader();

        $gateway = VersionGate::driver($factory->resolver($reader), $factory->localeStrategy($reader));

        return $gateway->extract($entry, 'Article');
    }

    public function test_the_same_content_yields_the_same_payload_from_either_profile(): void
    {
        $native = $this->extract('native', new FakeEntry([
            'title' => 'Learn English',
            'seo_title' => 'Learn English Online',
            'seo_description' => 'Structured English courses.',
            'seo_canonical' => 'https://example.com/canonical',
            'seo_image' => 'https://example.com/social.jpg',
        ]));

        $legacy = $this->extract('suffixed', new FakeEntry([
            'title' => 'Learn English',
            'seo_title' => 'Learn English Online',
            'description' => 'Structured English courses.',
            'canonical_link' => 'https://example.com/canonical',
            'social_image' => 'https://example.com/social.jpg',
        ]));

        foreach (['seo_title', 'seo_description', 'seo_canonical', 'seo_image'] as $field) {
            self::assertSame(
                $native['fields'][$field],
                $legacy['fields'][$field],
                "[{$field}] must carry the same value from both profiles.",
            );
            self::assertNotNull($native['fields'][$field], "[{$field}] resolved to null; parity would be vacuous.");
        }
    }

    public function test_a_profile_without_a_noindex_field_never_deindexes_a_page(): void
    {
        // The entry carries seo_noindex, but this profile does not map `robots`.
        // Reading it anyway would drop the page out of the index silently.
        $payload = $this->extract('suffixed', new FakeEntry(['seo_title' => 'x', 'seo_noindex' => true]));

        self::assertFalse($payload['fields']['noindex']);
    }

    public function test_a_profile_with_a_noindex_field_honours_it(): void
    {
        $payload = $this->extract('native', new FakeEntry(['seo_title' => 'x', 'seo_noindex' => true]));

        self::assertTrue($payload['fields']['noindex']);
    }

    public function test_the_multisite_profile_emits_no_alternates_for_a_lone_entry(): void
    {
        // FakeEntry exposes no sites()/in(), so this stands in for an entry that
        // belongs to a single site. Previously this path emitted an alternate
        // whose hreflang was the site HANDLE -- literally "default" on every
        // single-site project in the fleet.
        $payload = $this->extract('native', new FakeEntry(['seo_title' => 'x']));

        self::assertSame([], $payload['alternates']);
    }

    public function test_the_prefix_profile_only_advertises_translated_locales(): void
    {
        $translated = $this->extract('suffixed', new FakeEntry(['seo_title' => 'English', 'seo_title_ar' => 'عربي']));
        $untranslated = $this->extract('suffixed', new FakeEntry(['seo_title_ar' => 'عربي']));

        self::assertSame(['ar', 'x-default', 'en'], array_column($translated['alternates'], 'hreflang'));
        self::assertSame([], $untranslated['alternates']);
    }

    public function test_no_alternate_ever_carries_a_non_language_hreflang(): void
    {
        $payload = $this->extract('suffixed', new FakeEntry(['seo_title' => 'English', 'seo_title_ar' => 'عربي']));

        foreach ($payload['alternates'] as $alternate) {
            self::assertMatchesRegularExpression(
                '/^([a-z]{2,3}(-[A-Za-z0-9]+)*|x-default)$/',
                $alternate['hreflang'],
                'hreflang must be a BCP 47 tag; a Statamic site handle is not one.',
            );
        }
    }

    public function test_the_arabic_reading_uses_the_twin_handles(): void
    {
        $payload = $this->extract('suffixed', new FakeEntry([
            'seo_title' => 'English Title',
            'seo_title_ar' => 'عنوان عربي',
            'description' => 'English description.',
            'description_ar' => 'وصف عربي.',
        ]));

        // No request URL is bound in a unit test, so the strategy sees "/" and
        // resolves the unprefixed locale, which this config declares as Arabic.
        self::assertSame('عنوان عربي', $payload['fields']['seo_title']);
        self::assertSame('وصف عربي.', $payload['fields']['seo_description']);
        self::assertSame('ar', $payload['context']['locale']);
    }

    public function test_the_gateway_works_without_a_resolver(): void
    {
        // The no-argument form is what any caller predating this phase used.
        $payload = VersionGate::driver()->extract(new FakeEntry(['seo_title' => 'x']), 'Article');

        self::assertSame('x', $payload['fields']['seo_title']);
    }
}
