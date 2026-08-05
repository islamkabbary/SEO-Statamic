<?php

declare(strict_types=1);

namespace SilaSeo\Core\Context;

use SilaSeo\Core\Hreflang\Alternate;
use SilaSeo\Core\Support\TextDirection;

/**
 * Fluent, mutable builder for {@see SeoContext}. Bridges construct one with the
 * per-request essentials (url, locale, direction), pour in a resolved cascade
 * payload, and call {@see build()} to get the immutable context.
 */
final class SeoContextBuilder
{
    private ?string $title = null;
    private ?string $description = null;
    private ?string $image = null;
    private ?string $canonical = null;
    private ?string $ogType = null;
    private ?string $twitterCard = null;
    private ?string $twitterSite = null;
    private ?string $siteName = null;
    private ?string $titlePattern = null;

    /** @var list<string> */
    private array $robots = [];

    /** @var list<Alternate> */
    private array $alternates = [];

    /** @var list<array<string,mixed>> */
    private array $schema = [];

    /** @var array<string,mixed> */
    private array $custom = [];

    /** @var list<array{name?:string,property?:string,content:string}> */
    private array $meta = [];

    public function __construct(
        private readonly string $url,
        private readonly string $locale,
        private readonly TextDirection $direction,
    ) {
    }

    public static function for(string $url, string $locale, ?TextDirection $direction = null): self
    {
        return new self($url, $locale, $direction ?? TextDirection::forLocale($locale));
    }

    public function title(?string $title): self
    {
        $this->title = $this->clean($title);

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $this->clean($description);

        return $this;
    }

    public function image(?string $image): self
    {
        $this->image = $this->clean($image);

        return $this;
    }

    public function canonical(?string $canonical): self
    {
        $this->canonical = $this->clean($canonical);

        return $this;
    }

    public function ogType(?string $ogType): self
    {
        $this->ogType = $this->clean($ogType);

        return $this;
    }

    public function twitterCard(?string $twitterCard): self
    {
        $this->twitterCard = $this->clean($twitterCard);

        return $this;
    }

    public function twitterSite(?string $twitterSite): self
    {
        $this->twitterSite = $this->clean($twitterSite);

        return $this;
    }

    public function siteName(?string $siteName): self
    {
        $this->siteName = $this->clean($siteName);

        return $this;
    }

    public function titlePattern(?string $titlePattern): self
    {
        $this->titlePattern = $this->clean($titlePattern);

        return $this;
    }

    /**
     * @param list<string>|string|null $robots
     */
    public function robots(array|string|null $robots): self
    {
        $this->robots = $this->normaliseRobots($robots);

        return $this;
    }

    public function alternate(string $hreflang, string $url): self
    {
        $this->alternates[] = new Alternate($hreflang, $url);

        return $this;
    }

    /**
     * @param array<string,mixed> $node
     */
    public function schema(array $node): self
    {
        $this->schema[] = $node;

        return $this;
    }

    public function custom(string $key, mixed $value): self
    {
        $this->custom[$key] = $value;

        return $this;
    }

    /**
     * Add a custom <meta> tag (e.g. site verification). Pass $property=true for
     * an Open Graph-style property attribute instead of name.
     */
    public function metaTag(string $key, string $content, bool $property = false): self
    {
        $this->meta[] = $property
            ? ['property' => $key, 'content' => $content]
            : ['name' => $key, 'content' => $content];

        return $this;
    }

    /**
     * Apply a resolved cascade payload. Recognised keys map onto context fields;
     * everything else is carried through {@see SeoContext::$custom}.
     *
     * @param array<string,mixed> $payload
     */
    public function applyPayload(array $payload): self
    {
        $setters = [
            'title' => fn (mixed $v) => $this->title((string) $v),
            'description' => fn (mixed $v) => $this->description((string) $v),
            'image' => fn (mixed $v) => $this->image((string) $v),
            'canonical' => fn (mixed $v) => $this->canonical((string) $v),
            'og_type' => fn (mixed $v) => $this->ogType((string) $v),
            'twitter_card' => fn (mixed $v) => $this->twitterCard((string) $v),
            'twitter_site' => fn (mixed $v) => $this->twitterSite((string) $v),
            'site_name' => fn (mixed $v) => $this->siteName((string) $v),
            'title_pattern' => fn (mixed $v) => $this->titlePattern((string) $v),
            'robots' => fn (mixed $v) => $this->robots(is_array($v) ? $v : (string) $v),
        ];

        foreach ($payload as $key => $value) {
            if (isset($setters[$key])) {
                $setters[$key]($value);

                continue;
            }

            if ($key === 'schema' && is_array($value)) {
                foreach ($value as $node) {
                    if (is_array($node)) {
                        $this->schema($node);
                    }
                }

                continue;
            }

            $this->custom[(string) $key] = $value;
        }

        return $this;
    }

    public function build(): SeoContext
    {
        return new SeoContext(
            url: $this->url,
            locale: $this->locale,
            direction: $this->direction,
            title: $this->title,
            description: $this->description,
            image: $this->image,
            canonical: $this->canonical,
            ogType: $this->ogType,
            twitterCard: $this->twitterCard,
            twitterSite: $this->twitterSite,
            siteName: $this->siteName,
            titlePattern: $this->titlePattern,
            robots: $this->robots,
            alternates: $this->alternates,
            schema: $this->schema,
            custom: $this->custom,
            meta: $this->meta,
        );
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param list<string>|string|null $robots
     *
     * @return list<string>
     */
    private function normaliseRobots(array|string|null $robots): array
    {
        if ($robots === null) {
            return [];
        }

        $tokens = is_array($robots) ? $robots : explode(',', $robots);

        $normalised = [];

        foreach ($tokens as $token) {
            $token = strtolower(trim((string) $token));

            if ($token !== '' && ! in_array($token, $normalised, true)) {
                $normalised[] = $token;
            }
        }

        return $normalised;
    }
}