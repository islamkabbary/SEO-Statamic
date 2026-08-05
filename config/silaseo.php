<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default meta values (the root of the cascade)
    |--------------------------------------------------------------------------
    | Used whenever a page, model, or route does not provide its own value.
    | The cascade order is: these defaults -> DB settings -> bridge defaults
    | (collection/global) -> route registry -> page source -> runtime overrides.
    */
    'defaults' => [
        'site_name' => env('APP_NAME', 'Site'),
        'title_pattern' => '%title% %sep% %sitename%',
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
        'twitter_site' => null,
        'image' => null,
        'robots' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic site-wide structured data
    |--------------------------------------------------------------------------
    | When enabled, an Organization (and optional WebSite) JSON-LD node is added
    | to every page's @graph, de-duplicated by @id with any page-level schema.
    */
    'auto_schema' => true,

    'organization' => [
        'name' => env('APP_NAME', 'Site'),
        'url' => env('APP_URL', 'http://localhost'),
        'logo' => null,
        'same_as' => [],
        'telephone' => null,
        'email' => null,
    ],

    'website' => [
        'enabled' => true,
        // e.g. env('APP_URL') . '/search?q={search_term_string}'
        'search_url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale handling
    |--------------------------------------------------------------------------
    | Locales rendered right-to-left. hreflang is emitted only when a page
    | actually supplies alternates (off by default for session-locale sites).
    */
    'rtl_locales' => ['ar', 'fa', 'he', 'ur'],

    'integrations' => [
        'indexnow_key' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Statamic: field profile
    |--------------------------------------------------------------------------
    | Which entry handles carry which SEO meaning, so a project can adopt this
    | package without renaming its existing content.
    |
    | `native` matches the fieldset this package ships. `suffixed` is for the
    | older shape: one site, several languages by URL prefix, each language's
    | value in a twin handle on the same entry (title / title_ar).
    |
    | Resolution order for a logical field, given a locale:
    |   1. every mapped handle with the locale's suffix   (seo_title_ar, title_ar)
    |   2. every mapped handle bare                       (seo_title,    title)
    |   3. the profile's `defaults` entry
    |   4. null
    | An empty value never ends the walk -- blank means "not translated".
    |
    | A `null` handle means the project has no such field. It is NOT read from
    | somewhere else: an unmapped `robots` can never noindex a page by accident.
    */
    'statamic' => [

        'profile' => env('SILASEO_PROFILE', 'native'),

        'profiles' => [

            'native' => [
                'locale_strategy' => 'multisite',
                'fields' => [
                    'title' => 'seo_title',
                    'description' => 'seo_description',
                    'image' => 'seo_image',
                    'canonical' => 'seo_canonical',
                    'robots' => 'seo_noindex',
                    'focus_keyword' => 'seo_focus_keyword',
                    'content' => null,
                    'schema_type' => 'seo_schema_type',
                    'schema_json' => 'seo_schema_json',
                ],
                'fallbacks' => [
                    'title' => ['title'],
                ],
                'legacy_meta' => null,
            ],

            'suffixed' => [
                'locale_strategy' => 'prefix',
                'suffixes' => ['ar' => '_ar', 'en' => ''],
                'suffixable' => ['title', 'description', 'content', 'schema_json'],
                'fields' => [
                    'title' => 'seo_title',
                    'description' => 'description',
                    'image' => null,
                    'canonical' => 'canonical_link',
                    'robots' => null,
                    'focus_keyword' => null,
                    'content' => null,
                    'schema_type' => null,
                    'schema_json' => null,
                ],
                'fallbacks' => [
                    'title' => ['title'],
                ],
                // Hand-written markup from before structured fields existed. Read
                // only to fill a gap a mapped field left empty -- never above one.
                'legacy_meta' => 'meta_tags',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Statamic: URL-prefixed locales
        |----------------------------------------------------------------------
        | Only used by the `prefix` locale strategy. The locale is read from the
        | request path, never from Statamic's site config -- some projects mutate
        | that per request from the session, which config:cache then freezes.
        |
        | An alternate is emitted only for a locale the entry has a title for, so
        | a page that was never translated is not advertised as if it were.
        |
        |   'ar' => ['prefix' => '',   'hreflang' => 'ar', 'x_default' => true],
        |   'en' => ['prefix' => 'en', 'hreflang' => 'en'],
        */
        'locales' => [],

        'links' => [
            'ttl' => 600,
            'max_targets' => 1000,
        ],
    ],
];