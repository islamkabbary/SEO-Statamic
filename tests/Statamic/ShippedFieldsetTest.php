<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The shipped fieldset is what `vendor:publish --tag=silaseo-fieldset` gives a new
 * host, and it is the only place the field handles are declared. It had drifted
 * from what the code actually reads: `seo_focus_keyword` and `seo_report` were
 * missing, so a fresh install got no analysis panel and every keyword check
 * silently scored against an empty string. Nothing failed loudly -- the checks
 * just quietly measured nothing.
 */
final class ShippedFieldsetTest extends TestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function fields(): array
    {
        $path = dirname(__DIR__, 2) . '/resources/fieldsets/seo.yaml';
        self::assertFileExists($path);

        /** @var array{fields?: list<array{handle?: string, field?: array<string, mixed>}>} $parsed */
        $parsed = Yaml::parseFile($path);

        $fields = [];

        foreach ($parsed['fields'] ?? [] as $field) {
            if (isset($field['handle'])) {
                $fields[(string) $field['handle']] = $field['field'] ?? [];
            }
        }

        return $fields;
    }

    /**
     * Every handle the package reads off an entry. Kept as a literal list so that
     * adding a read without publishing the field fails here.
     *
     * @return array<string, array{string}>
     */
    public static function requiredHandles(): array
    {
        return [
            'focus keyword drives the keyword checks and link suggestions' => ['seo_focus_keyword'],
            'title' => ['seo_title'],
            'description' => ['seo_description'],
            'social image' => ['seo_image'],
            'canonical override' => ['seo_canonical'],
            'noindex toggle' => ['seo_noindex'],
            'schema type' => ['seo_schema_type'],
            'raw JSON-LD override' => ['seo_schema_json'],
            'the analysis panel itself' => ['seo_report'],
        ];
    }

    /**
     * @dataProvider requiredHandles
     */
    public function test_it_declares_every_handle_the_package_reads(string $handle): void
    {
        self::assertArrayHasKey($handle, $this->fields());
    }

    public function test_the_analysis_panel_uses_this_addons_fieldtype(): void
    {
        $fields = $this->fields();

        // The handle must match SeoReport::handle(), which Statamic derives from the
        // class basename. A mismatch throws FieldtypeNotFoundException and takes the
        // whole publish form down, not just this field.
        self::assertSame('seo_report', $fields['seo_report']['type'] ?? null);
    }

    public function test_the_analysis_panel_is_not_localizable(): void
    {
        // It stores no value of its own -- SeoReport::process() returns null -- so a
        // per-site copy would be meaningless.
        self::assertFalse($this->fields()['seo_report']['localizable'] ?? true);
    }

    public function test_content_fields_are_localizable(): void
    {
        foreach (['seo_title', 'seo_description', 'seo_image', 'seo_canonical', 'seo_focus_keyword'] as $handle) {
            self::assertTrue(
                $this->fields()[$handle]['localizable'] ?? false,
                "{$handle} must be localizable; the multisite projects rely on per-site values.",
            );
        }
    }
}
