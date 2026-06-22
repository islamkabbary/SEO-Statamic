<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Gateway;

/**
 * Quarantines every Statamic API that differs across major versions (v4/v5/v6):
 * value augmentation, asset URLs, sites/locales, and entry localizations.
 * Concrete drivers are selected at boot by {@see VersionGate}.
 */
interface StatamicGateway
{
    /**
     * Extract an entry's SEO inputs into the shape {@see \SilaSeo\Statamic\EntryMapper} expects.
     *
     * @return array{
     *     fields: array<string,mixed>,
     *     context: array<string,mixed>,
     *     alternates: list<array{hreflang:string,url:string}>
     * }
     */
    public function extract(object $entry, string $schemaType): array;
}