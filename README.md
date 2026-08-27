# SilaSEO

**An in-house SEO toolkit for the Statamic fleet.** Meta & Open Graph/Twitter tags,
canonical URLs, hreflang, JSON-LD schema, XML sitemap, redirects, a 404 monitor,
live content analysis, internal-link suggestions, and IndexNow — with a Control Panel
that runs on **Statamic 4, 5 and 6**.

---

## Table of contents

- [Features](#features)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Quick start](#quick-start)
- [SEO fields](#seo-fields)
- [The SEO analysis panel](#the-seo-analysis-panel)
- [Control Panel tools](#control-panel-tools)
- [Sitemap, robots.txt & IndexNow](#sitemap-robotstxt--indexnow)
- [Configuration](#configuration)
- [Programmatic API](#programmatic-api)
- [Multilingual & hreflang](#multilingual--hreflang)
- [Using it in plain Laravel](#using-it-in-plain-laravel)
- [Statamic 4 / 5 / 6 support](#statamic-4--5--6-support)
- [Development](#development)
- [License](#license)

---

## Features

- **Meta & social tags** — title, description, canonical, robots, Open Graph and Twitter cards, resolved through a predictable cascade.
- **Structured data (JSON-LD)** — automatic `Organization` + `WebSite` nodes, per-page `Article`/`BlogPosting` schema, or a raw `@graph` override per entry.
- **XML sitemap** — every published, indexable entry across all sites, with hreflang alternates and images.
- **Redirects** — managed in the Control Panel, with CSV import/export and a self-loop guard.
- **404 monitor** — a flat-file log of missed URLs, sorted by hit count, viewable and clearable in the CP.
- **Live SEO analysis** — an in-form panel showing a score, a pass/warn/fail checklist, and SERP + social previews that update as you type (Arabic content aware).
- **Internal-link suggestions** — surfaces relevant existing entries for the focus keyword.
- **IndexNow** — pings search engines automatically when an entry is saved or deleted.
- **robots.txt** — served dynamically and editable from the SEO settings.
- **Configurable field mapping** — adopt the package on a project whose fields are named differently, without renaming any content.

## Compatibility

| Requirement | Supported |
|---|---|
| **Statamic** | 4.x · 5.x · 6.x |
| **PHP** | 8.1+ |
| **Laravel** | 10 · 11 · 12 · 13 |

Every feature — including the in-form SEO analysis panel — works identically across
Statamic 4, 5 and 6. See [Statamic 4 / 5 / 6 support](#statamic-4--5--6-support) for how
the Control Panel assets are shipped per version.

---

## Installation

The package is distributed from a Git repository. Add it as a Composer VCS repository,
then require it.

**1. Add the repository** to your project's `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/islamkabbary/SEO-Statamic.git" }
]
```

**2. Require the package:**

```bash
composer require silaseo/seo
```

The service providers and the `Seo` facade are auto-discovered — no manual registration.

**3. Publish the Control Panel assets** (required so the analysis panel renders):

```bash
php artisan vendor:publish --provider="SilaSeo\Statamic\ServiceProvider" --force
```

**4. Publish what you need** (all optional):

```bash
# Configuration file -> config/silaseo.php
php artisan vendor:publish --tag=silaseo-config

# The SEO fieldset -> resources/fieldsets/seo.yaml (skip if you keep your own)
php artisan vendor:publish --tag=silaseo-fieldset

# Database migrations (only for the plain-Laravel / Eloquent integration)
php artisan vendor:publish --tag=silaseo-migrations
```

> **Re-publishing:** whenever you update the package, re-run the `--provider … --force`
> command so the newest Control Panel bundle is copied into `public/`.

---

## Quick start

### 1. Add the SEO fields to your blueprints

Import the shipped fieldset into any collection or page blueprint:

```yaml
# resources/blueprints/collections/pages/page.yaml
tabs:
  main:
    sections:
      -
        fields:
          - import: seo
```

This adds the [SEO fields](#seo-fields) and the live [analysis panel](#the-seo-analysis-panel)
to the publish form.

### 2. Render the SEO `<head>`

Apply the site-wide settings and the current entry, then output the head. The cleanest
place is your layout.

**Blade layout** (recommended):

```blade
@php
    silaseo_settings();                      // site-wide defaults + Organization schema
    if (isset($page)) silaseo_entry($page);  // this entry's meta, JSON-LD and hreflang
@endphp
<!doctype html>
<html lang="{{ $site->locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    @seoHead   {{-- prints all title/meta/link/JSON-LD tags --}}
</head>
```

You can also use the component `<x-seo-head />` instead of `@seoHead`.

**Antlers layout:**

```antlers
<head>
    <meta charset="utf-8" />
    {{ seo }}   {{# renders the resolved SEO head block #}}
</head>
```

> `silaseo_entry($entry, $schemaType = 'Article')` applies an entry's SEO to the request
> and returns the entry. `silaseo_settings()` applies the site-wide **SEO Settings** global.

### 3. Fill in the SEO Settings global (optional but recommended)

Create a global set named `seo_settings` to hold site-wide defaults (site name, default
share image, Organization details, robots.txt body, IndexNow key). These feed the cascade
and the automatic Organization schema.

---

## SEO fields

The bundled `seo` fieldset provides these handles:

| Handle | Type | Purpose |
|---|---|---|
| `seo_focus_keyword` | text | The phrase the page should rank for — drives the analysis and link suggestions. |
| `seo_title` | text | Overrides the `<title>` in search results. Falls back to the entry title. |
| `seo_description` | textarea | The meta description (~155 chars). |
| `seo_image` | assets (1) | Open Graph / Twitter share image. |
| `seo_canonical` | text | Canonical URL. Blank self-references the page. |
| `seo_noindex` | toggle | Hide the page from search engines. |
| `seo_schema_type` | select | Structured-data type (Default / Article / Blog Posting). |
| `seo_schema_json` | code | Raw JSON-LD override (object, array, or `@graph`). Replaces the auto node. |
| `seo_report` | seo_report | The live [analysis panel](#the-seo-analysis-panel) (stores nothing). |

Every field is localizable. If your project already stores these under different handles,
map them instead of renaming content — see [field profiles](#field-profiles).

---

## The SEO analysis panel

Adding `seo_report` to a blueprint renders a panel inside the publish form that shows,
live as you edit:

- A **score** (0–100) with a colour-coded rating.
- A **pass / warn / fail checklist** (title length, description, keyword usage, readability, …), with **Arabic content support** (readability, harakat/alef normalisation).
- A **Google SERP preview** and a **social share preview**.
- **Internal-link suggestions** for the focus keyword.

The analysis runs server-side; the panel only renders it. It works on Statamic 4, 5 and 6
(see [below](#statamic-4--5--6-support)).

> For Arabic analysis, optionally install [`khaled.alshamaa/ar-php`](https://packagist.org/packages/khaled.alshamaa/ar-php)
> for improved readability and slug transliteration.

---

## Control Panel tools

Under the **SEO** section in the Control Panel navigation:

### Redirects

A grid of `from → to → status` rules stored in the `seo_settings` global.

- **Import / export CSV** from the Redirects page.
- Source paths match however they were spelled (leading slash, trailing slash, encoding).
- A self-referential rule that would loop is ignored.

### 404 log

A flat-file log of requested URLs that returned 404, sorted by hit count. View it, then
clear it, from the CP. Bounded and safely serialised so it can't grow unbounded.

---

## Sitemap, robots.txt & IndexNow

### robots.txt

Served automatically at `/robots.txt` from the `seo_settings` global. No setup needed.

### IndexNow

Set an IndexNow key in the SEO settings (or `config('silaseo.integrations.indexnow_key')`).
The key-verification file is served automatically at `/{key}.txt`, and saving or deleting
an entry pings IndexNow in the background.

### Sitemap

The controller is provided but **not routed for you**, so you control the URL. Register it
in your site's `routes/web.php`:

```php
use SilaSeo\Statamic\Http\Controllers\SitemapController;

Route::get('sitemap.xml', SitemapController::class);
```

It emits every published, indexable entry across all sites with hreflang alternates and
image entries.

---

## Configuration

Publish and edit `config/silaseo.php`. Highlights:

### Cascade defaults

```php
'defaults' => [
    'site_name'     => env('APP_NAME', 'Site'),
    'title_pattern' => '%title% %sep% %sitename%',
    'og_type'       => 'website',
    'twitter_card'  => 'summary_large_image',
    'twitter_site'  => null,
    'image'         => null,
    'robots'        => null,
],
```

Resolution order: config defaults → DB/global settings → collection/global bridge →
route registry → page source → runtime overrides.

### Automatic schema

```php
'auto_schema'  => true,
'organization' => ['name' => env('APP_NAME'), 'url' => env('APP_URL'), 'logo' => null, 'same_as' => [], ...],
'website'      => ['enabled' => true, 'search_url' => null],
```

An `Organization` (and optional `WebSite`) node is added to every page's `@graph`,
de-duplicated by `@id` against any page-level schema.

### RTL locales

```php
'rtl_locales' => ['ar', 'fa', 'he', 'ur'],
```

### Field profiles

Map which entry handles carry which SEO meaning, so a project can adopt the package
**without renaming existing content**. Nothing branches on a project name.

```php
'statamic' => [
    'profile' => env('SILASEO_PROFILE', 'native'),   // which profile below to use
    'profiles' => [
        'native' => [                                 // matches the shipped fieldset
            'locale_strategy' => 'multisite',
            'fields' => [
                'title'       => 'seo_title',
                'description' => 'seo_description',
                'image'       => 'seo_image',
                'canonical'   => 'seo_canonical',
                'robots'      => 'seo_noindex',
                'focus_keyword' => 'seo_focus_keyword',
                'schema_type' => 'seo_schema_type',
                'schema_json' => 'seo_schema_json',
            ],
            'fallbacks' => ['title' => ['title']],
        ],
        'suffixed' => [ /* legacy: one site, per-language twin handles (title / title_ar) */ ],
    ],
],
```

Resolution per logical field, per locale: every mapped handle with the locale suffix →
every mapped handle bare → the profile default → `null`. An **unmapped** field resolves to
`null`/`false`, so a project without a `robots` field can never deindex a page by accident.

### Locale strategies

Three strategies replace the single-multisite assumption:

- `multisite` — the default; one Statamic site per locale.
- `prefix` — the locale is read from the request path (`/en/…`, `/ar/…`), never from
  Statamic's site config. Configure the locales under `statamic.locales`.
- `singlesite` — a single-locale site.

### Internal links

```php
'statamic' => [
    'links' => ['ttl' => 600, 'max_targets' => 1000],
],
```

---

## Programmatic API

Every page's SEO is accumulated on a request-scoped **meta service**, reachable via the
`seo()` helper or the `Seo` facade. Use it to set SEO on custom routes, or to override
anything the cascade resolved.

```php
use SilaSeo\Laravel\Facades\Seo;

Seo::title('Pricing')
   ->description('Simple, transparent plans.')
   ->image('https://example.com/og/pricing.png')
   ->canonical('https://example.com/pricing')
   ->robots(['index', 'follow'])
   ->alternate('ar', 'https://example.com/ar/pricing')
   ->schema(['@type' => 'Product', 'name' => 'Pro plan']);
```

| Method | Description |
|---|---|
| `for(mixed $source)` | Apply a source (an array payload or a `SeoSource`) in one call. |
| `defaults(array $payload)` | Set the cascade defaults. |
| `title` / `description` / `image` / `canonical` | Set individual tags. |
| `robots(array\|string)` / `noindex(bool $follow = true)` | Robots directives. |
| `alternate(string $hreflang, string $url)` | Add an hreflang alternate. |
| `schema(array $node)` | Add a JSON-LD node to the `@graph`. |
| `render()` | Get the resolved `SeoResult`. |
| `head()` | Render the full `<head>` HTML block. |
| `jsonLd()` | Render just the JSON-LD `<script>`. |
| `httpHeaders()` | Get SEO-related HTTP headers (e.g. `X-Robots-Tag`). |

Rendering helpers: `@seoHead` (Blade directive), `<x-seo-head />` (Blade component),
`{{ seo }}` (Antlers), or `seo()->head()`.

### Middleware

Aliases are registered for optional wiring:

| Alias | Effect |
|---|---|
| `silaseo.redirects` | Apply redirect rules. |
| `silaseo.404` | Log 404s. |
| `silaseo.robots` | Send the `X-Robots-Tag` header. |
| `silaseo.inject` | **Last-resort** — inject the SEO head before `</head>` for layouts you cannot edit. Do not combine with `@seoHead`. |

In Statamic, the redirect and 404-logging middleware are attached to the `web` group
automatically.

---

## Multilingual & hreflang

- `hreflang` alternates are emitted **only** when a page actually supplies them, so a
  page that was never translated is not advertised as if it were.
- Locale tags are normalised to BCP-47; anything that isn't a real language tag is dropped
  (so single-site installs never emit `hreflang="default"`).
- RTL locales drive the direction of the SERP/social previews.
- Choose how locales are detected with the [locale strategy](#locale-strategies).

---

## Using it in plain Laravel

Beyond Statamic, the `SilaSeo\Laravel` layer works in any Laravel app. Give an Eloquent
model SEO with the `HasSeo` trait (backed by a polymorphic `seo_meta` table — publish and
run the migrations first):

```php
use Illuminate\Database\Eloquent\Model;
use SilaSeo\Laravel\Concerns\HasSeo;
use SilaSeo\Laravel\Contracts\SeoSource;

class Post extends Model implements SeoSource
{
    use HasSeo;

    protected function defaultSeoPayload(string $locale): array
    {
        return [
            'title'       => $this->name,
            'description' => $this->excerpt,
            'image'       => $this->cover_url,
            'schema'      => ['@type' => 'Article', 'headline' => $this->name],
        ];
    }
}
```

Then in the controller / view:

```php
Seo::for($post);   // or seo()->for($post);
```

Stored per-record overrides (via the `seo_meta` relation) are merged over the model's
default payload.

---

## Statamic 4 / 5 / 6 support

The server-side features (meta, schema, sitemap, redirects, 404, robots, IndexNow) are
version-agnostic. The **in-form analysis panel** is a Vue component, and Statamic ships two
Control Panel generations:

- **Statamic 6** runs Vue 3. The panel is delivered through **Vite** (`resources/dist/`).
- **Statamic 4 & 5** run Vue 2.7. The panel is delivered as a **pre-built Vue 2.7 script**
  (`resources/dist-legacy/js/cp-legacy.js`) through the addon's `$scripts`.

The correct bundle is chosen automatically from the running Statamic major version — you
only ever run the publish command. Both bundles are committed, so **consumers never need
Node or npm.** If the version can't be determined, no CP script is shipped and the field
degrades to a display-only label (the rest of the publish form is unaffected).

---

## Development

Only needed if you change the Control Panel components (`resources/js/`). Consumers install
the committed bundles and never build.

```bash
npm install

# Statamic 6 bundle (Vue 3, Vite). Requires the @statamic/cms dev dependency.
npm run build

# Statamic 4/5 bundle (Vue 2.7, IIFE). Builds anywhere.
npm run build:legacy
```

Commit both `resources/dist/` and `resources/dist-legacy/` after a component change, and
bump `lib/Version.php` in the same commit that creates a release tag.

Run the test suite:

```bash
composer install
vendor/bin/phpunit
```

---

## License

Proprietary — internal use within the fleet. Not for public distribution.
