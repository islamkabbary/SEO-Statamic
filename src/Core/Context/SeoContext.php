<?php

declare(strict_types=1);

namespace SilaSeo\Core\Context;

use SilaSeo\Core\Hreflang\Alternate;
use SilaSeo\Core\Support\TextDirection;

/**
 * The single, host-agnostic description of everything needed to render a page's
 * SEO <head>. Every delivery path (Blade component, Antlers tag, Livewire trait,
 * response middleware, headless API) resolves to one of these, so output is
 * identical regardless of how the page was built.
 */
final class SeoContext
{
    /**
     * @param list<string>             $robots     Robots directives, e.g. ['noindex', 'follow'].
     * @param list<Alternate>          $alternates Hreflang alternates for this page.
     * @param list<array<string,mixed>> $schema    Resolved JSON-LD nodes (each an associative array).
     * @param array<string,mixed>      $custom     Arbitrary extra values carried through the cascade.
     */
    public function __construct(
        public readonly string $url,
        public readonly string $locale,
        public readonly TextDirection $direction,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $image = null,
        public readonly ?string $canonical = null,
        public readonly ?string $ogType = null,
        public readonly ?string $twitterCard = null,
        public readonly ?string $twitterSite = null,
        public readonly ?string $siteName = null,
        public readonly ?string $titlePattern = null,
        public readonly array $robots = [],
        public readonly array $alternates = [],
        public readonly array $schema = [],
        public readonly array $custom = [],
        public readonly array $meta = [],
    ) {
    }

    /**
     * The effective canonical URL: the explicit canonical when set, otherwise
     * the page URL itself (self-referential).
     */
    public function effectiveCanonical(): string
    {
        return $this->canonical !== null && $this->canonical !== ''
            ? $this->canonical
            : $this->url;
    }
}