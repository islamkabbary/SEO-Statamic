<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fields;

use Throwable;

/**
 * Reads a handle off a real Statamic entry.
 *
 * Entry::value() is identical in Statamic 4, 5 and 6: it reads raw data without
 * consulting the blueprint, and returns null for a handle that does not exist
 * rather than throwing. No version branching is needed.
 */
final class EntryValueReader implements ValueReader
{
    public function read(object $entry, string $handle): mixed
    {
        try {
            if (! method_exists($entry, 'value')) {
                return null;
            }

            return $entry->value($handle);
        } catch (Throwable) {
            // A missing collection or a broken augmentation must never take down
            // the page the meta is being rendered for.
            return null;
        }
    }
}
