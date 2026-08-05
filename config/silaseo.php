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
];