<?php

declare(strict_types=1);

namespace SilaSeo\Core\Meta;

use SilaSeo\Core\Context\SeoContext;
use SilaSeo\Core\Hreflang\HreflangResolver;
use SilaSeo\Core\Support\Locale;

/**
 * Produces the ordered list of {@see MetaTag}s for a page: title, description,
 * canonical, robots, Open Graph, Twitter Card, and hreflang alternates.
 */
final class MetaBuilder
{
    private const DEFAULT_OG_TYPE = 'website';
    private const DEFAULT_TWITTER_CARD = 'summary_large_image';

    public function __construct(
        private readonly TitleFormatter $titleFormatter = new TitleFormatter(),
        private readonly HreflangResolver $hreflang = new HreflangResolver(),
        private readonly FieldMapper $mapper = new FieldMapper(),
    ) {
    }

    /**
     * @return list<MetaTag>
     */
    public function build(SeoContext $context): array
    {
        $formattedTitle = $this->titleFormatter->format(
            $context->title,
            $context->titlePattern,
            $context->siteName,
        );

        $tags = [];

        if ($formattedTitle !== '') {
            $tags[] = MetaTag::title($formattedTitle);
        }

        if ($context->description !== null) {
            $tags[] = MetaTag::name('description', $context->description);
        }

        $tags[] = MetaTag::link('canonical', $context->effectiveCanonical());

        if ($context->robots !== []) {
            $tags[] = MetaTag::name('robots', implode(', ', $context->robots));
        }

        $tags = [...$tags, ...$this->openGraphTags($context, $formattedTitle)];
        $tags = [...$tags, ...$this->twitterTags($context, $formattedTitle)];

        foreach ($context->meta as $meta) {
            if (isset($meta['property'])) {
                $tags[] = MetaTag::property((string) $meta['property'], (string) ($meta['content'] ?? ''));
            } elseif (isset($meta['name'])) {
                $tags[] = MetaTag::name((string) $meta['name'], (string) ($meta['content'] ?? ''));
            }
        }

        $tags = [...$tags, ...$this->hreflang->resolve($context->alternates)];

        return $tags;
    }

    /**
     * @return list<MetaTag>
     */
    private function openGraphTags(SeoContext $context, string $formattedTitle): array
    {
        $ogTitle = $this->mapper->firstNonEmpty($context->title, $formattedTitle);

        $tags = [
            MetaTag::property('og:type', $context->ogType ?? self::DEFAULT_OG_TYPE),
            MetaTag::property('og:url', $context->effectiveCanonical()),
            MetaTag::property('og:locale', Locale::openGraph($context->locale)),
        ];

        if ($ogTitle !== null) {
            $tags[] = MetaTag::property('og:title', $ogTitle);
        }

        if ($context->description !== null) {
            $tags[] = MetaTag::property('og:description', $context->description);
        }

        if ($context->siteName !== null) {
            $tags[] = MetaTag::property('og:site_name', $context->siteName);
        }

        if ($context->image !== null) {
            $tags[] = MetaTag::property('og:image', $context->image);
        }

        foreach ($this->alternateLocales($context) as $locale) {
            $tags[] = MetaTag::property('og:locale:alternate', $locale);
        }

        return $tags;
    }

    /**
     * @return list<MetaTag>
     */
    private function twitterTags(SeoContext $context, string $formattedTitle): array
    {
        $tags = [
            MetaTag::name('twitter:card', $context->twitterCard ?? self::DEFAULT_TWITTER_CARD),
        ];

        if ($context->twitterSite !== null) {
            $tags[] = MetaTag::name('twitter:site', $context->twitterSite);
        }

        $twitterTitle = $this->mapper->firstNonEmpty($context->title, $formattedTitle);

        if ($twitterTitle !== null) {
            $tags[] = MetaTag::name('twitter:title', $twitterTitle);
        }

        if ($context->description !== null) {
            $tags[] = MetaTag::name('twitter:description', $context->description);
        }

        if ($context->image !== null) {
            $tags[] = MetaTag::name('twitter:image', $context->image);
        }

        return $tags;
    }

    /**
     * Open Graph alternate locales: every hreflang alternate except x-default
     * and the page's own locale, de-duplicated.
     *
     * @return list<string>
     */
    private function alternateLocales(SeoContext $context): array
    {
        $current = Locale::openGraph($context->locale);
        $locales = [];

        foreach ($context->alternates as $alternate) {
            if ($alternate->isXDefault()) {
                continue;
            }

            $locale = Locale::openGraph($alternate->hreflang);

            if ($locale !== $current && ! in_array($locale, $locales, true)) {
                $locales[] = $locale;
            }
        }

        return $locales;
    }
}