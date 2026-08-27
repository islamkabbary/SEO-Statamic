<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Locale;

use SilaSeo\Statamic\Fields\FieldMap;

/**
 * One site, one language, no alternates.
 *
 * The safe default, and deliberately the fallback whenever the configured
 * strategy cannot be built. Emitting no hreflang is always correct-if-incomplete;
 * emitting a wrong one actively misdirects a crawler, and that is the failure mode
 * this replaces -- the previous code fed Statamic's site HANDLE into the attribute
 * and produced hreflang="default" on every single-site project in the fleet.
 */
final class SingleSiteLocaleStrategy implements LocaleStrategy
{
    public function __construct(private readonly string $locale = 'en')
    {
    }

    public function name(): string
    {
        return 'single';
    }

    public function current(): string
    {
        return $this->locale;
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return [$this->locale];
    }

    /**
     * @return list<array{hreflang: string, url: string}>
     */
    public function alternatesFor(object $entry, FieldMap $map): array
    {
        return [];
    }
}
