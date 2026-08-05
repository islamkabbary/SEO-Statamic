<?php

declare(strict_types=1);

namespace SilaSeo;

/**
 * The shipped package version.
 *
 * Host projects previously consumed this package as a copied directory, which
 * left no way to tell which revision a site was actually running — one project
 * silently sat a whole release behind. This constant is the marker the
 * `silaseo:version` command reports, so drift is visible instead of invisible.
 *
 * Bump it in the same commit that creates the git tag.
 */
final class Version
{
    public const CURRENT = '1.0.0';

    public static function current(): string
    {
        return self::CURRENT;
    }
}
