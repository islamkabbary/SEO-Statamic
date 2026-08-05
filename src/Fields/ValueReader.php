<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fields;

/**
 * Reads one raw handle off an entry.
 *
 * The one place that touches a Statamic Entry for field values, so everything
 * above it — the resolver, the locale strategies — is exercisable with a plain
 * object in tests and needs no Statamic installed.
 */
interface ValueReader
{
    public function read(object $entry, string $handle): mixed;
}
