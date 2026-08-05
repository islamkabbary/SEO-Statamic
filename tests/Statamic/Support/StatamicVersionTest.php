<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Support;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Support\AssetStrategy;
use SilaSeo\Statamic\Support\StatamicVersion;

final class StatamicVersionTest extends TestCase
{
    protected function tearDown(): void
    {
        StatamicVersion::forget();

        parent::tearDown();
    }

    /**
     * @return array<string, array{?string, ?int}>
     */
    public static function versions(): array
    {
        return [
            // Exactly what Composer reports for each project in the fleet.
            'elnokhba, normalised' => ['4.58.3.0', 4],
            'elnokhba, pretty' => ['v4.58.3', 4],
            'pro_co, normalised' => ['5.73.21.0', 5],
            'pro_co, pretty' => ['v5.73.21', 5],
            'silaeng, normalised' => ['6.24.2.0', 6],
            'silaeng, pretty' => ['v6.24.2', 6],
            // lara_proonline pins 4.x-dev, which Composer normalises like this.
            'lara_proonline, normalised dev branch' => ['4.9999999.9999999.9999999-dev', 4],
            'lara_proonline, pretty dev branch' => ['4.x-dev', 4],
            'dev prefix form' => ['dev-4.x', 4],
            'plain major' => ['6', 6],
            'future major' => ['7.0.0.0', 7],
            'unparseable' => ['dev-main', null],
            'empty' => ['', null],
            'null' => [null, null],
            'zero is not a major' => ['0.1.0', null],
        ];
    }

    /**
     * @dataProvider versions
     */
    public function test_it_parses_the_major_version(?string $version, ?int $expected): void
    {
        self::assertSame($expected, StatamicVersion::parse($version));
    }

    public function test_an_undetermined_version_is_reported_as_unknown_not_guessed(): void
    {
        StatamicVersion::swap(null);

        // Guessing wrong would ship a Vue 3 bundle into a Vue 2 Control Panel,
        // which fails at load. Declining to ship assets is recoverable.
        self::assertNull(StatamicVersion::major());
        self::assertFalse(StatamicVersion::atLeast(4));
        self::assertFalse(StatamicVersion::atLeast(6));
    }

    public function test_at_least_compares_against_the_detected_major(): void
    {
        StatamicVersion::swap(5);

        self::assertTrue(StatamicVersion::atLeast(4));
        self::assertTrue(StatamicVersion::atLeast(5));
        self::assertFalse(StatamicVersion::atLeast(6));
    }

    public function test_it_detects_a_version_in_this_repository(): void
    {
        // statamic/cms is a hard requirement but is not installed in the package's
        // own dev tree, so detection must degrade to null rather than throw.
        StatamicVersion::forget();

        $major = StatamicVersion::major();

        self::assertTrue($major === null || $major >= 4);
    }

    /**
     * @return array<string, array{?int, AssetStrategy}>
     */
    public static function strategies(): array
    {
        return [
            'statamic 4 has a Vue 2 control panel' => [4, AssetStrategy::None],
            'statamic 5 has a Vue 2 control panel' => [5, AssetStrategy::None],
            'statamic 6 has a Vue 3 control panel' => [6, AssetStrategy::Vite],
            'a future major is assumed forward compatible' => [7, AssetStrategy::Vite],
            'unknown ships nothing' => [null, AssetStrategy::None],
        ];
    }

    /**
     * @dataProvider strategies
     */
    public function test_it_picks_an_asset_strategy_for_each_major(?int $major, AssetStrategy $expected): void
    {
        self::assertSame($expected, AssetStrategy::for($major));
    }

    public function test_only_the_vite_strategy_ships_vue_components(): void
    {
        self::assertTrue(AssetStrategy::Vite->shipsVueComponents());
        self::assertFalse(AssetStrategy::None->shipsVueComponents());
    }

    public function test_the_current_strategy_follows_the_detected_version(): void
    {
        StatamicVersion::swap(4);
        self::assertSame(AssetStrategy::None, AssetStrategy::current());

        StatamicVersion::swap(6);
        self::assertSame(AssetStrategy::Vite, AssetStrategy::current());
    }
}
