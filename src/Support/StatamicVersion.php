<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Support;

use Composer\InstalledVersions;
use Throwable;

/**
 * The running Statamic major version.
 *
 * Read from Composer's runtime API rather than Statamic::version(), because the
 * answer is needed inside ServiceProvider::register() -- before the application
 * has booted -- to decide which Control Panel assets to register. Statamic's own
 * accessor resolves through the container, which is not something to lean on that
 * early.
 *
 * Returns null rather than guessing when the version cannot be determined.
 * A wrong guess would ship a Vue 3 bundle into a Vue 2 Control Panel, which fails
 * at load with no useful error; declining to ship assets is recoverable.
 */
final class StatamicVersion
{
    private const PACKAGE = 'statamic/cms';

    private static ?int $memo = null;

    private static bool $resolved = false;

    /**
     * Pure parse of a version string to its major number. The seam these tests
     * exercise -- everything else here touches Composer or the filesystem.
     *
     * Handles the shapes Composer actually produces: "6.24.2.0", "v4.58.3", and
     * the normalised form of a dev branch, "4.9999999.9999999.9999999-dev",
     * which is what a `4.x-dev` pin reports.
     */
    public static function parse(?string $version): ?int
    {
        if ($version === null) {
            return null;
        }

        $version = ltrim(trim($version), 'vV');

        // "dev-main" carries no major; "dev-4.x" does.
        if (str_starts_with($version, 'dev-')) {
            $version = substr($version, 4);
        }

        if (preg_match('/^(\d+)/', $version, $matches) !== 1) {
            return null;
        }

        $major = (int) $matches[1];

        return $major > 0 ? $major : null;
    }

    public static function major(): ?int
    {
        if (self::$resolved) {
            return self::$memo;
        }

        self::$resolved = true;

        return self::$memo = self::detect();
    }

    public static function atLeast(int $major): bool
    {
        $current = self::major();

        return $current !== null && $current >= $major;
    }

    /**
     * Force a version. Test seam only.
     */
    public static function swap(?int $major): void
    {
        self::$memo = $major;
        self::$resolved = true;
    }

    public static function forget(): void
    {
        self::$memo = null;
        self::$resolved = false;
    }

    private static function detect(): ?int
    {
        try {
            if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled(self::PACKAGE)) {
                // getVersion() is the normalised form and is always present;
                // getPrettyVersion() can be null for some install types.
                $major = self::parse(InstalledVersions::getVersion(self::PACKAGE))
                    ?? self::parse(InstalledVersions::getPrettyVersion(self::PACKAGE));

                if ($major !== null) {
                    return $major;
                }
            }
        } catch (Throwable) {
            // Fall through to the filesystem probe.
        }

        return self::probeFilesystem();
    }

    /**
     * Last resort for installs Composer's runtime API cannot describe.
     *
     * `resources/dist-package` is the npm entry point Statamic 6 ships for addon
     * builds and does not exist in 4 or 5; `resources/svg/icons/light` is the
     * 4/5 icon layout, dropped in 6.
     */
    private static function probeFilesystem(): ?int
    {
        try {
            if (! function_exists('base_path')) {
                return null;
            }

            $cms = base_path('vendor/statamic/cms/resources');

            if (is_dir($cms . '/dist-package')) {
                return 6;
            }

            if (is_dir($cms . '/svg/icons/light')) {
                // Distinguishing 4 from 5 is not possible here, and nothing this
                // class gates needs to: they behave identically for our purposes.
                return 5;
            }
        } catch (Throwable) {
            // Unknown.
        }

        return null;
    }
}
