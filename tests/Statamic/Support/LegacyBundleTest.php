<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Guards the committed Statamic 4/5 Control Panel bundle (resources/dist-legacy/js/cp-legacy.js)
 * and the ServiceProvider wiring that ships it. The bundle is a pre-built artifact: consumers
 * never run npm, so its shape is verified here rather than at build time.
 *
 * These are static file checks (no Statamic boot) because statamic/cms is not installed in the
 * package's own dev tree -- the same reason AddonDiscoveryTest reads composer.json as text.
 */
final class LegacyBundleTest extends TestCase
{
    private const BUNDLE = 'resources/dist-legacy/js/cp-legacy.js';

    private function root(): string
    {
        return dirname(__DIR__, 3) . '/';
    }

    private function bundle(): string
    {
        $path = $this->root() . self::BUNDLE;
        self::assertFileExists($path, 'Run `npm run build:legacy` and commit the output.');

        return (string) file_get_contents($path);
    }

    public function test_the_bundle_is_a_classic_script_not_an_es_module(): void
    {
        $js = $this->bundle();

        // Shipped via AddonServiceProvider::$scripts as a plain <script src>, which cannot
        // execute ES modules. The IIFE build must not emit top-level import/export.
        self::assertStringStartsNotWith('import', ltrim($js));
        self::assertStringNotContainsString("\nexport ", $js);
        self::assertStringNotContainsString("\nexport{", $js);
    }

    public function test_it_registers_the_seo_report_fieldtype(): void
    {
        $js = $this->bundle();

        self::assertStringContainsString('seo_report-fieldtype', $js);
        self::assertStringContainsString('booting', $js);
    }

    public function test_vue_is_externalised_not_bundled(): void
    {
        $js = $this->bundle();

        // Vue 2.7 is provided by the Control Panel at runtime; bundling a second copy would
        // break the CP. The build maps `vue` to the global, so the artifact never imports it.
        self::assertStringNotContainsString("from\"vue\"", $js);
        self::assertStringNotContainsString("from'vue'", $js);
        self::assertStringNotContainsString('require("vue")', $js);
    }

    public function test_the_component_styles_are_inlined(): void
    {
        // cssInjectedByJs() folds the SFC <style> blocks into the single JS artifact, so the
        // addon's CSS class prefix must appear in the bundle text.
        self::assertStringContainsString('silaseo-', $this->bundle());
    }

    public function test_the_service_provider_ships_the_legacy_bundle(): void
    {
        $provider = (string) file_get_contents($this->root() . 'src/ServiceProvider.php');

        self::assertStringContainsString(self::BUNDLE, $provider);
        self::assertStringContainsString('$this->scripts', $provider);
    }
}
