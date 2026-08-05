<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Doubles;

/**
 * A stand-in for a Statamic entry.
 *
 * Entry::value() reads raw data without consulting the blueprint and returns null
 * for an unknown handle -- identical in Statamic 4, 5 and 6 -- so a plain array
 * behind that one method reproduces the read surface the package depends on, and
 * the whole field-resolution layer can be tested with no Statamic installed.
 */
final class FakeEntry
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(private array $values = [])
    {
    }

    public function value(string $handle): mixed
    {
        return $this->values[$handle] ?? null;
    }

    public function set(string $handle, mixed $value): self
    {
        $this->values[$handle] = $value;

        return $this;
    }
}
