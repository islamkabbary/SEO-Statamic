<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Gateway;

use Statamic\Statamic;

/**
 * Selects the {@see StatamicGateway} driver matching the running Statamic major
 * version. V5/V4 drivers are introduced in later rollout phases; until then the
 * v6 driver (read-compatible with v5) is used as the default.
 */
final class VersionGate
{
    public static function driver(): StatamicGateway
    {
        $major = (int) explode('.', (string) Statamic::version())[0];

        return match (true) {
            $major >= 6 => new V6Driver(),
            default => new V6Driver(),
        };
    }
}