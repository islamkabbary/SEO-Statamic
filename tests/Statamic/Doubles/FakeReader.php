<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Doubles;

use SilaSeo\Statamic\Fields\ValueReader;

/**
 * Reads {@see FakeEntry}, standing in for the real Statamic-backed reader.
 */
final class FakeReader implements ValueReader
{
    public function read(object $entry, string $handle): mixed
    {
        return method_exists($entry, 'value') ? $entry->value($handle) : null;
    }
}
