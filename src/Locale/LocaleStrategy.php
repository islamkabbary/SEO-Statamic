<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Locale;

use SilaSeo\Statamic\Fields\FieldMap;

/**
 * How a project expresses "the same page in another language".
 *
 * Three shapes exist across the fleet and they are genuinely different, not
 * variations of one:
 *
 *  - real Statamic multisite, where a localization is a separate entry;
 *  - a single site with URL-prefixed locales, where the "other language" is the
 *    same entry read through a different set of field handles;
 *  - a single site with one language, where there is no other version at all.
 *
 * Everything version-specific about Statamic stays out of here; this is about the
 * project's content model.
 */
interface LocaleStrategy
{
    /**
     * A stable identifier, used to discriminate cached derivatives.
     */
    public function name(): string;

    /**
     * The content locale for the current request.
     *
     * Implementations must not read the locale out of Statamic's site config on
     * the projects that mutate it per request: two of them call session_start()
     * inside config/statamic/sites.php and rewrite the single site's locale from
     * $_SESSION at config-load time. That value is frozen by config:cache and is
     * wrong under any warm cache, so it cannot be the source of truth.
     */
    public function current(): string;

    /**
     * Every locale this project publishes.
     *
     * @return list<string>
     */
    public function locales(): array;

    /**
     * hreflang alternates for an entry.
     *
     * Only pages that genuinely exist may be returned. An alternate pointing at a
     * URL that 404s, or at a localization that was never created, is worse than
     * emitting nothing: it tells a crawler the translation exists.
     *
     * @return list<array{hreflang: string, url: string}>
     */
    public function alternatesFor(object $entry, FieldMap $map): array;
}
