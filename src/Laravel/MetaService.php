<?php

declare(strict_types=1);

namespace SilaSeo\Laravel;

use SilaSeo\Core\Cascade\DefaultsResolver;
use SilaSeo\Core\Context\SeoContext;
use SilaSeo\Core\Context\SeoContextBuilder;
use SilaSeo\Core\Render\HeadRenderer;
use SilaSeo\Core\Render\SeoResult;
use SilaSeo\Core\Schema\Types\Organization;
use SilaSeo\Core\Schema\Types\WebSite;
use SilaSeo\Core\Support\TextDirection;
use SilaSeo\Laravel\Contracts\SeoSource;
use SilaSeo\Laravel\Routing\SeoRegistry;
use SilaSeo\Laravel\Settings\SettingsRepository;

/**
 * The request-scoped hub every page type writes into and one renderer reads
 * from, so Blade, Antlers, Livewire, and headless paths all emit identical SEO.
 *
 * Bound as a SCOPED service (reset per request) so it is Octane-safe.
 *
 * Cascade order (least to most specific):
 *   config defaults -> DB settings -> bridge defaults -> route registry
 *   -> page source -> runtime overrides.
 */
class MetaService
{
    /** @var array<string,mixed> */
    private array $bridgeDefaults = [];

    /** @var array<string,mixed> */
    private array $sourcePayload = [];

    /** @var array<string,mixed> */
    private array $overrides = [];

    /** @var list<array{hreflang:string,url:string}> */
    private array $alternates = [];

    /** @var list<array<string,mixed>> */
    private array $extraSchema = [];

    /** @var list<array{key:string,content:string,property:bool}> */
    private array $metaTags = [];

    /** @var array<string,mixed>|null */
    private ?array $organizationOverride = null;

    /** @var array<string,mixed>|null */
    private ?array $websiteOverride = null;

    private ?string $url = null;

    private ?string $locale = null;

    private ?SeoResult $rendered = null;

    /**
     * @param array<string,mixed> $config The full `silaseo` config array.
     */
    public function __construct(
        private readonly DefaultsResolver $resolver,
        private readonly HeadRenderer $renderer,
        private readonly SeoRegistry $registry,
        private readonly SettingsRepository $settings,
        private readonly array $config = [],
    ) {
    }

    /**
     * Set the primary page source: a SeoSource model, anything exposing
     * toSeoPayload(), a raw payload array, or a route-registry key.
     */
    public function for(mixed $source): self
    {
        $this->sourcePayload = match (true) {
            $source instanceof SeoSource => $source->toSeoPayload($this->locale),
            is_object($source) && method_exists($source, 'toSeoPayload') => (array) $source->toSeoPayload($this->locale),
            is_array($source) => $source,
            is_string($source) => $this->registry->payload($source),
            default => [],
        };

        return $this->invalidate();
    }

    /**
     * Merge collection/global defaults (used by the Statamic bridge).
     *
     * @param array<string,mixed> $payload
     */
    public function defaults(array $payload): self
    {
        $this->bridgeDefaults = [...$this->bridgeDefaults, ...$payload];

        return $this->invalidate();
    }

    /**
     * Override the site-wide Organization used for auto JSON-LD (e.g. fed from a
     * CMS "SEO Settings" global instead of config).
     *
     * @param array<string,mixed> $organization
     */
    public function organization(array $organization): self
    {
        $this->organizationOverride = $organization;

        return $this->invalidate();
    }

    /**
     * @param array<string,mixed> $website
     */
    public function website(array $website): self
    {
        $this->websiteOverride = $website;

        return $this->invalidate();
    }

    /**
     * Add a custom <meta> tag (e.g. google-site-verification). Pass $property=true
     * for an Open Graph-style property attribute.
     */
    public function meta(string $name, string $content, bool $property = false): self
    {
        $this->metaTags[] = ['key' => $name, 'content' => $content, 'property' => $property];

        return $this->invalidate();
    }

    public function forUrl(string $url): self
    {
        $this->url = $url;

        return $this->invalidate();
    }

    public function forLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this->invalidate();
    }

    public function title(string $title): self
    {
        return $this->override('title', $title);
    }

    public function description(string $description): self
    {
        return $this->override('description', $description);
    }

    public function image(string $image): self
    {
        return $this->override('image', $image);
    }

    public function canonical(string $canonical): self
    {
        return $this->override('canonical', $canonical);
    }

    public function ogType(string $ogType): self
    {
        return $this->override('og_type', $ogType);
    }

    /**
     * @param list<string>|string $robots
     */
    public function robots(array|string $robots): self
    {
        return $this->override('robots', $robots);
    }

    public function noindex(bool $follow = true): self
    {
        return $this->override('robots', ['noindex', $follow ? 'follow' : 'nofollow']);
    }

    public function alternate(string $hreflang, string $url): self
    {
        $this->alternates[] = ['hreflang' => $hreflang, 'url' => $url];

        return $this->invalidate();
    }

    /**
     * @param array<string,mixed> $node
     */
    public function schema(array $node): self
    {
        $this->extraSchema[] = $node;

        return $this->invalidate();
    }

    public function render(): SeoResult
    {
        return $this->rendered ??= $this->renderer->render($this->resolveContext());
    }

    public function head(): string
    {
        return $this->render()->headHtml;
    }

    public function jsonLd(): string
    {
        return $this->render()->jsonLd;
    }

    /**
     * @return array<string,string>
     */
    public function httpHeaders(): array
    {
        return $this->render()->httpHeaders;
    }

    /**
     * Clear all per-request state. Useful in tests and long-lived workers.
     */
    public function reset(): self
    {
        $this->bridgeDefaults = [];
        $this->sourcePayload = [];
        $this->overrides = [];
        $this->alternates = [];
        $this->extraSchema = [];
        $this->metaTags = [];
        $this->url = null;
        $this->locale = null;

        return $this->invalidate();
    }

    public function resolveContext(): SeoContext
    {
        $url = $this->url ?? $this->currentUrl();
        $locale = $this->locale ?? $this->currentLocale();

        $payload = $this->resolver->resolve(...array_values(array_filter([
            $this->configDefaults(),
            $this->settings->all(),
            $this->bridgeDefaults,
            $this->routePayload(),
            $this->sourcePayload,
            $this->overrides,
        ])));

        $builder = SeoContextBuilder::for($url, $locale, $this->directionFor($locale))
            ->applyPayload($payload);

        foreach ($this->alternates as $alternate) {
            $builder->alternate($alternate['hreflang'], $alternate['url']);
        }

        foreach ($this->autoSchema() as $node) {
            $builder->schema($node);
        }

        foreach ($this->extraSchema as $node) {
            $builder->schema($node);
        }

        foreach ($this->metaTags as $meta) {
            $builder->metaTag($meta['key'], $meta['content'], $meta['property']);
        }

        return $builder->build();
    }

    private function override(string $key, mixed $value): self
    {
        $this->overrides[$key] = $value;

        return $this->invalidate();
    }

    private function invalidate(): self
    {
        $this->rendered = null;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    private function configDefaults(): array
    {
        return (array) ($this->config['defaults'] ?? []);
    }

    /**
     * @return array<string,mixed>
     */
    private function routePayload(): array
    {
        if (! function_exists('request') || ! app()->bound('request')) {
            return [];
        }

        return $this->registry->payloadForRequest(request());
    }

    /**
     * Site-wide Organization (+ WebSite) JSON-LD built from config, de-duplicated
     * by @id against any page-level schema in {@see \SilaSeo\Core\Schema\Graph}.
     *
     * @return list<array<string,mixed>>
     */
    private function autoSchema(): array
    {
        if (($this->config['auto_schema'] ?? false) !== true) {
            return [];
        }

        $organization = $this->organizationOverride ?? (array) ($this->config['organization'] ?? []);
        $name = $organization['name'] ?? null;
        $url = $organization['url'] ?? null;

        if (! is_string($name) || ! is_string($url) || $name === '' || $url === '') {
            return [];
        }

        $base = rtrim($url, '/');
        $nodes = [];

        $nodes[] = (new Organization($name, $url))
            ->withId($base . '/#organization')
            ->logo($organization['logo'] ?? null)
            ->sameAs((array) ($organization['same_as'] ?? []))
            ->telephone($organization['telephone'] ?? null)
            ->email($organization['email'] ?? null)
            ->toArray();

        $website = $this->websiteOverride ?? (array) ($this->config['website'] ?? []);

        if (($website['enabled'] ?? false) === true) {
            $site = (new WebSite($name, $url))
                ->withId($base . '/#website')
                ->publisherId($base . '/#organization');

            if (is_string($website['search_url'] ?? null) && $website['search_url'] !== '') {
                $site->searchAction($website['search_url']);
            }

            $nodes[] = $site->toArray();
        }

        return $nodes;
    }

    private function directionFor(string $locale): TextDirection
    {
        $rtl = (array) ($this->config['rtl_locales'] ?? []);
        $primary = strtolower(explode('-', str_replace('_', '-', $locale))[0]);

        if (in_array($primary, array_map('strtolower', $rtl), true)) {
            return TextDirection::Rtl;
        }

        return TextDirection::forLocale($locale);
    }

    private function currentUrl(): string
    {
        if (function_exists('url') && app()->bound('request')) {
            return url()->current();
        }

        return '';
    }

    private function currentLocale(): string
    {
        return app()->getLocale();
    }
}