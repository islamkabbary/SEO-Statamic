<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Gateway;

use SilaSeo\Statamic\Fields\AssetUrl;
use SilaSeo\Statamic\Fields\FieldResolver;
use SilaSeo\Statamic\Locale\LocaleStrategy;
use Throwable;

/**
 * Statamic v6 implementation of {@see StatamicGateway}. Every call is defensive:
 * any unexpected entry shape degrades to null/empty rather than breaking a page.
 *
 * In practice the read surface used here (value(), augmentedValue(), site(),
 * absoluteUrl()) is also compatible with Statamic v5; dedicated V5/V4 drivers
 * are added in later phases where the APIs genuinely diverge.
 */
final class V6Driver implements StatamicGateway
{
    public function __construct(
        private readonly ?FieldResolver $resolver = null,
        private readonly ?LocaleStrategy $locale = null,
    ) {
    }

    public function extract(object $entry, string $schemaType): array
    {
        // Every field read goes through the resolver, so a project whose meta
        // description lives in `description` and whose Arabic copy lives in
        // `description_ar` is served without renaming any content. The legacy
        // hardcoded reads remain only as the no-resolver fallback.
        $resolver = $this->resolver;
        $locale = $this->locale?->current() ?? $this->locale($entry);

        return [
            'fields' => [
                'seo_title' => $resolver?->string($entry, 'title', $locale) ?? $this->raw($entry, 'seo_title'),
                'seo_description' => $resolver?->string($entry, 'description', $locale) ?? $this->raw($entry, 'seo_description'),
                'seo_canonical' => $resolver?->string($entry, 'canonical', $locale) ?? $this->raw($entry, 'seo_canonical'),
                'seo_image' => $resolver?->assetUrl($entry, 'image', $locale) ?? $this->assetUrl($entry, 'seo_image'),
                // An unmapped robots key is false, never a stray read: a project
                // without a noindex toggle must not have pages silently deindexed.
                'noindex' => $resolver !== null
                    ? $resolver->bool($entry, 'robots', $locale)
                    : (bool) $this->raw($entry, 'seo_noindex'),
                'schema_json' => $resolver?->string($entry, 'schema_json', $locale) ?? $this->raw($entry, 'seo_schema_json'),
            ],
            'context' => [
                'fallback_title' => $resolver?->string($entry, 'title', $locale) ?? $this->raw($entry, 'title'),
                'fallback_image' => null,
                'url' => $this->url($entry),
                'locale' => $locale,
                'schema_type' => $this->firstFilled(
                    $resolver?->string($entry, 'schema_type', $locale) ?? $this->raw($entry, 'seo_schema_type'),
                    $schemaType,
                ),
            ],
            // Delegated: which pages count as existing translations is a property
            // of the project's content model, not of the Statamic version.
            'alternates' => $this->locale !== null && $resolver !== null
                ? $this->locale->alternatesFor($entry, $resolver->map())
                : $this->alternates($entry),
        ];
    }

    private function raw(object $entry, string $handle): mixed
    {
        try {
            return $entry->value($handle);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * First non-empty string; the "default" sentinel from the schema-type select
     * is treated as empty so the template-supplied default wins.
     */
    private function firstFilled(mixed ...$values): string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '' && $value !== 'default') {
                return $value;
            }
        }

        return 'Article';
    }

    private function assetUrl(object $entry, string $handle): ?string
    {
        try {
            // Delegated so the multi-asset case is handled. The old inline guard
            // probed method_exists($value, 'first'), which is FALSE for
            // Statamic\Assets\OrderedQueryBuilder -- it reaches first() through
            // __call -- so every assets field with max_files !== 1 silently
            // resolved to null and the page emitted no og:image at all.
            return AssetUrl::from($entry->augmentedValue($handle)->value());
        } catch (Throwable) {
            return null;
        }
    }

    private function url(object $entry): ?string
    {
        try {
            if (method_exists($entry, 'absoluteUrl')) {
                return $entry->absoluteUrl();
            }

            if (method_exists($entry, 'url')) {
                return $entry->url();
            }
        } catch (Throwable) {
            // Fall through.
        }

        return null;
    }

    private function locale(object $entry): ?string
    {
        try {
            if (method_exists($entry, 'locale')) {
                return $entry->locale();
            }

            if (method_exists($entry, 'site')) {
                return $entry->site()->lang();
            }
        } catch (Throwable) {
            // Fall through.
        }

        return null;
    }

    /**
     * @return list<array{hreflang:string,url:string}>
     */
    private function alternates(object $entry): array
    {
        try {
            if (! method_exists($entry, 'sites') || ! method_exists($entry, 'in')) {
                return [];
            }

            $alternates = [];

            foreach ($entry->sites() as $siteHandle) {
                $localized = $entry->in($siteHandle);

                if ($localized === null || ($this->isPublished($localized) === false)) {
                    continue;
                }

                $url = $this->url($localized);
                $hreflang = $this->locale($localized) ?? $siteHandle;

                if (is_string($url) && $url !== '') {
                    $alternates[] = ['hreflang' => $hreflang, 'url' => $url];
                }
            }

            return $alternates;
        } catch (Throwable) {
            return [];
        }
    }

    private function isPublished(object $entry): bool
    {
        try {
            return method_exists($entry, 'published') ? (bool) $entry->published() : true;
        } catch (Throwable) {
            return true;
        }
    }
}