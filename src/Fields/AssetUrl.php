<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fields;

use Throwable;

/**
 * The URL of the first asset in an augmented `assets` value.
 *
 * The shape varies and the old guard got it wrong. Statamic augments an assets
 * field to a single Asset when `max_files === 1`, and otherwise to a
 * `Statamic\Assets\OrderedQueryBuilder`. That builder reaches `first()` through
 * `__call`, so `method_exists($value, 'first')` is FALSE for it -- the previous
 * check silently skipped every multi-asset field and returned null, meaning any
 * project whose social image field allowed more than one file emitted no
 * og:image at all, on every Statamic major.
 *
 * Statamic 5 and 6 additionally gained `statamic.system.always_augment_to_query`,
 * which turns even a max_files=1 field into a builder. Recognising the contract
 * rather than probing for a method covers both.
 */
final class AssetUrl
{
    public static function from(mixed $value): ?string
    {
        try {
            $asset = self::first($value);

            if (! is_object($asset)) {
                return is_string($asset) && $asset !== '' ? $asset : null;
            }

            foreach (['absoluteUrl', 'url'] as $method) {
                if (method_exists($asset, $method)) {
                    $url = $asset->{$method}();

                    if (is_string($url) && $url !== '') {
                        return $url;
                    }
                }
            }
        } catch (Throwable) {
            // A broken asset container must never take down the page.
        }

        return null;
    }

    private static function first(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // instanceof against a class that is not loaded returns false without
        // invoking the autoloader, so these are safe with no Statamic installed.
        if ($value instanceof \Statamic\Contracts\Query\Builder) {
            return $value->get()->first();
        }

        if ($value instanceof \Illuminate\Support\Enumerable) {
            return $value->first();
        }

        if (is_array($value)) {
            return $value === [] ? null : reset($value);
        }

        // A plain object exposing first() -- e.g. a value collection.
        if (is_object($value) && ! method_exists($value, 'url') && method_exists($value, 'first')) {
            return $value->first();
        }

        return $value;
    }
}
