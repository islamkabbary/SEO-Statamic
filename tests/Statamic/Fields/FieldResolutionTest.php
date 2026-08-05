<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Fields;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Fields\FieldMap;
use SilaSeo\Statamic\Fields\FieldResolver;
use SilaSeo\Statamic\Locale\SingleSiteLocaleStrategy;
use SilaSeo\Statamic\Tests\Doubles\FakeEntry;
use SilaSeo\Statamic\Tests\Doubles\FakeReader;

/**
 * The resolution order, and the rule that an empty value never ends the walk.
 */
final class FieldResolutionTest extends TestCase
{
    /**
     * A project storing its meta description in `description`, its canonical in
     * `canonical_link`, and a second Arabic copy of each in an `_ar` twin.
     * Deliberately not named after any project.
     */
    private function suffixedMap(): FieldMap
    {
        return FieldMap::fromArray([
            'locale_strategy' => 'prefix',
            'suffixes' => ['ar' => '_ar', 'en' => ''],
            'fields' => [
                'title' => 'seo_title',
                'description' => 'description',
                'canonical' => 'canonical_link',
                'image' => 'social_image',
                'robots' => null,
                'focus_keyword' => null,
                'content' => 'body',
            ],
            'fallbacks' => [
                'title' => ['title'],
                'image' => ['main_image'],
            ],
            'legacy_meta' => 'meta_tags',
        ]);
    }

    private function resolver(FieldMap $map, FakeEntry $entry, string $locale): FieldResolver
    {
        return new FieldResolver($map, new FakeReader(), new SingleSiteLocaleStrategy($locale));
    }

    public function test_the_candidate_order_puts_every_localised_handle_before_any_base_handle(): void
    {
        // The other order serves an English seo_title to an Arabic visitor whose
        // entry has title_ar.
        self::assertSame(
            ['seo_title_ar', 'title_ar', 'seo_title', 'title'],
            $this->suffixedMap()->candidates('title', 'ar'),
        );
    }

    public function test_a_locale_with_no_suffix_only_tries_the_base_handles(): void
    {
        self::assertSame(['seo_title', 'title'], $this->suffixedMap()->candidates('title', 'en'));
    }

    public function test_an_unconfigured_locale_falls_back_to_the_base_handles(): void
    {
        self::assertSame(['seo_title', 'title'], $this->suffixedMap()->candidates('title', 'fr'));
    }

    /**
     * @return array<string, array{array<string, mixed>, string, ?string}>
     */
    public static function arabicResolution(): array
    {
        return [
            'the localised SEO field wins' => [
                ['seo_title_ar' => 'عنوان سيو', 'title_ar' => 'عنوان', 'seo_title' => 'SEO Title', 'title' => 'Title'],
                'ar',
                'عنوان سيو',
            ],
            'falls back to the localised twin of the base field' => [
                ['title_ar' => 'عنوان', 'seo_title' => 'SEO Title', 'title' => 'Title'],
                'ar',
                'عنوان',
            ],
            'falls back to the base SEO field when nothing is translated' => [
                ['seo_title' => 'SEO Title', 'title' => 'Title'],
                'ar',
                'SEO Title',
            ],
            'falls back to the base field last' => [
                ['title' => 'Title'],
                'ar',
                'Title',
            ],
            'nothing at all resolves to null' => [
                [],
                'ar',
                null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $values
     *
     * @dataProvider arabicResolution
     */
    public function test_it_walks_the_full_fallback_chain(array $values, string $locale, ?string $expected): void
    {
        $resolver = $this->resolver($this->suffixedMap(), new FakeEntry($values), $locale);

        self::assertSame($expected, $resolver->string(new FakeEntry($values), 'title', $locale));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function emptyValues(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'spaces' => ['   '],
            'tab and newline' => ["\t\n"],
            'empty array' => [[]],
            'false' => [false],
        ];
    }

    /**
     * @dataProvider emptyValues
     */
    public function test_an_empty_value_does_not_stop_the_walk(mixed $empty): void
    {
        // A blank seo_title_ar means "not translated", not "the title is blank".
        // Accepting it as an answer is what leaves pages with an empty <title>.
        $entry = new FakeEntry(['seo_title_ar' => $empty, 'title_ar' => $empty, 'seo_title' => 'Fallback']);

        self::assertSame('Fallback', $this->resolver($this->suffixedMap(), $entry, 'ar')->string($entry, 'title', 'ar'));
    }

    /**
     * @dataProvider emptyValues
     */
    public function test_an_empty_value_is_never_reported_as_the_answering_handle(mixed $empty): void
    {
        $entry = new FakeEntry(['seo_title_ar' => $empty, 'seo_title' => 'Fallback']);

        self::assertSame('seo_title', $this->resolver($this->suffixedMap(), $entry, 'ar')->handleUsed($entry, 'title', 'ar'));
    }

    public function test_zero_is_a_real_value(): void
    {
        // "0" is a legitimate title for a page about zero; only blanks are absent.
        $entry = new FakeEntry(['seo_title' => '0', 'title' => 'Fallback']);

        self::assertSame('0', $this->resolver($this->suffixedMap(), $entry, 'en')->string($entry, 'title', 'en'));
    }

    public function test_it_falls_back_to_a_configured_default(): void
    {
        $map = FieldMap::fromArray([
            'fields' => ['title' => 'seo_title'],
            'defaults' => ['title' => 'Untitled'],
        ]);
        $entry = new FakeEntry([]);

        self::assertSame('Untitled', $this->resolver($map, $entry, 'en')->string($entry, 'title', 'en'));
    }

    public function test_an_unmapped_key_resolves_to_null_rather_than_erroring(): void
    {
        // Not every project has a noindex toggle or a focus keyword.
        $entry = new FakeEntry(['seo_noindex' => true]);
        $resolver = $this->resolver($this->suffixedMap(), $entry, 'en');

        self::assertFalse($this->suffixedMap()->has('robots'));
        self::assertNull($resolver->string($entry, 'robots', 'en'));
        self::assertFalse($resolver->bool($entry, 'robots', 'en'));
    }

    public function test_an_unmapped_toggle_never_reports_true_from_a_stray_handle(): void
    {
        // The entry has seo_noindex set, but this profile does not map `robots`.
        // Reading it anyway would silently noindex the page.
        $entry = new FakeEntry(['seo_noindex' => true]);

        self::assertFalse($this->resolver($this->suffixedMap(), $entry, 'en')->bool($entry, 'robots', 'en'));
    }

    public function test_a_mapped_toggle_reads_through_the_map(): void
    {
        $map = FieldMap::fromArray(['fields' => ['robots' => 'seo_noindex']]);
        $entry = new FakeEntry(['seo_noindex' => true]);

        self::assertTrue($this->resolver($map, $entry, 'en')->bool($entry, 'robots', 'en'));
    }

    public function test_it_resolves_the_canonical_through_a_project_specific_handle(): void
    {
        $entry = new FakeEntry(['canonical_link' => 'https://example.com/real']);

        self::assertSame(
            'https://example.com/real',
            $this->resolver($this->suffixedMap(), $entry, 'en')->string($entry, 'canonical', 'en'),
        );
    }

    public function test_values_are_trimmed(): void
    {
        $entry = new FakeEntry(['seo_title' => "  Padded  \n"]);

        self::assertSame('Padded', $this->resolver($this->suffixedMap(), $entry, 'en')->string($entry, 'title', 'en'));
    }

    public function test_the_map_signature_changes_with_the_profile(): void
    {
        $a = $this->suffixedMap()->signature();
        $b = FieldMap::fromArray(['fields' => ['title' => 'seo_title']])->signature();

        self::assertNotSame($a, $b);
        self::assertSame($a, $this->suffixedMap()->signature(), 'The signature must be stable for the same profile.');
    }

    public function test_all_handles_covers_suffixed_variants_and_the_legacy_field(): void
    {
        $all = $this->suffixedMap()->allHandles();

        foreach (['seo_title', 'seo_title_ar', 'title', 'title_ar', 'description', 'description_ar', 'meta_tags', 'meta_tags_ar'] as $handle) {
            self::assertContains($handle, $all);
        }
    }
}
