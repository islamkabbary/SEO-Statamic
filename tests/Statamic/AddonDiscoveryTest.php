<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Guards the composer.json contract that Statamic's addon loader depends on.
 *
 * Statamic\Extend\Manifest::formatPackage() resolves an addon's on-disk root like this:
 *
 *     $provider  = $package['extra']['laravel']['providers'][0];
 *     $namespace = implode('\', explode('\', $provider, -1));
 *     $autoload  = $package['autoload']['psr-4'][$namespace.'\'];      // unguarded
 *     $directory = Str::removeRight(dirname($reflector->getFileName()), rtrim($autoload, '/'));
 *
 * Two things follow, and both have already broken this package once:
 *
 *  1. The FIRST listed provider's namespace must have its own explicit psr-4 key.
 *     A catch-all "SilaSeo\" => "src/" does not satisfy the lookup, and the miss is
 *     an undefined-array-key fatal during boot, not a graceful skip.
 *
 *  2. $directory is derived by stripping the psr-4 path off the provider's directory,
 *     so everything AddonServiceProvider reads relative to it — views, translations,
 *     publishables, the Vite manifest — must live at the package ROOT. An earlier
 *     attempt to ship as one package (f9f530e) left those under packages/statamic/
 *     while declaring the repo root as the addon, and was reverted (0afc985) for
 *     exactly this reason.
 */
final class AddonDiscoveryTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function composerJson(): array
    {
        $path = dirname(__DIR__, 2) . '/composer.json';
        self::assertFileExists($path);

        /** @var array<string, mixed> $json */
        $json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $json;
    }

    private function resolvedAddonDirectory(): string
    {
        $package = $this->composerJson();

        $provider = $package['extra']['laravel']['providers'][0] ?? null;
        self::assertIsString($provider, 'extra.laravel.providers[0] must be set.');

        $namespace = implode('\\', explode('\\', $provider, -1));
        $psr4 = $package['autoload']['psr-4'] ?? [];

        self::assertArrayHasKey(
            $namespace . '\\',
            $psr4,
            "Manifest::formatPackage() looks up psr-4[\"{$namespace}\\\\\"] for the first provider "
            . 'and does not guard the access. Add an explicit key for it.',
        );

        $autoload = $psr4[$namespace . '\\'];
        $providerFile = dirname(__DIR__, 2) . '/' . $autoload . 'ServiceProvider.php';
        self::assertFileExists($providerFile, 'The first provider must resolve through its own psr-4 path.');

        $directory = str_replace('\\', '/', dirname($providerFile));
        $suffix = rtrim($autoload, '/');

        self::assertStringEndsWith($suffix, $directory);

        return substr($directory, 0, -strlen($suffix));
    }

    public function test_the_first_provider_namespace_has_an_explicit_psr4_key(): void
    {
        self::assertDirectoryExists($this->resolvedAddonDirectory());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function addonRelativeAssets(): array
    {
        return [
            'views' => ['resources/views'],
            'translations' => ['resources/lang'],
            'fieldset' => ['resources/fieldsets/seo.yaml'],
            'vite manifest' => ['resources/dist/build/manifest.json'],
            'cp routes' => ['routes/cp.php'],
            'web routes' => ['routes/web.php'],
            // formatPackage() reads this from the resolved directory.
            'composer.json' => ['composer.json'],
        ];
    }

    /**
     * @dataProvider addonRelativeAssets
     */
    public function test_addon_assets_resolve_from_the_derived_directory(string $relative): void
    {
        self::assertFileExists($this->resolvedAddonDirectory() . $relative);
    }

    public function test_the_publish_path_does_not_collide_with_statamic_core(): void
    {
        $name = $this->composerJson()['name'] ?? '';
        self::assertIsString($name);

        // AddonServiceProvider publishes assets to public/vendor/{packageName}/, where
        // packageName is the segment after the slash. Naming this package
        // "silaseo/statamic" would target public/vendor/statamic/ — the same directory
        // Statamic core publishes its own CP build into.
        $packageName = explode('/', $name)[1] ?? '';

        self::assertNotSame('statamic', $packageName);
    }

    public function test_it_declares_itself_a_statamic_addon(): void
    {
        $package = $this->composerJson();

        self::assertSame('statamic-addon', $package['type'] ?? null);
        self::assertArrayHasKey('statamic', $package['extra'] ?? []);
    }
}
