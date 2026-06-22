<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Gateway;

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
    public function extract(object $entry, string $schemaType): array
    {
        return [
            'fields' => [
                'seo_title' => $this->raw($entry, 'seo_title'),
                'seo_description' => $this->raw($entry, 'seo_description'),
                'seo_canonical' => $this->raw($entry, 'seo_canonical'),
                'seo_image' => $this->assetUrl($entry, 'seo_image'),
                'noindex' => (bool) $this->raw($entry, 'seo_noindex'),
                'schema_json' => $this->raw($entry, 'seo_schema_json'),
            ],
            'context' => [
                'fallback_title' => $this->raw($entry, 'title'),
                'fallback_image' => null,
                'url' => $this->url($entry),
                'locale' => $this->locale($entry),
                'schema_type' => $this->firstFilled($this->raw($entry, 'seo_schema_type'), $schemaType),
            ],
            'alternates' => $this->alternates($entry),
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
            $value = $entry->augmentedValue($handle)->value();

            if (is_object($value) && method_exists($value, 'first')) {
                $value = $value->first();
            }

            if (is_object($value) && method_exists($value, 'absoluteUrl')) {
                return $value->absoluteUrl();
            }

            if (is_object($value) && method_exists($value, 'url')) {
                return $value->url();
            }
        } catch (Throwable) {
            // Fall through to null.
        }

        return null;
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