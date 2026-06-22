<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use SilaSeo\Laravel\Models\SeoMeta;

/**
 * Gives an Eloquent model full SEO: a polymorphic seo_meta relation plus a
 * cascade payload. Models map their own attributes to SEO fields (and build
 * type-specific schema) by overriding {@see defaultSeoPayload()}.
 *
 * The host model should also `implements \SilaSeo\Laravel\Contracts\SeoSource`.
 */
trait HasSeo
{
    public function seoMeta(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'seoable');
    }

    /**
     * @return array<string,mixed>
     */
    public function toSeoPayload(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $stored = $this->resolveSeoMeta($locale)?->payload ?? [];

        return array_replace($this->defaultSeoPayload($locale), $stored);
    }

    /**
     * Per-model attribute mapping and schema generation. Override per model:
     * e.g. return ['title' => $this->name, 'description' => $this->excerpt,
     *              'image' => $this->cover_url, 'schema' => [...]].
     *
     * @return array<string,mixed>
     */
    protected function defaultSeoPayload(string $locale): array
    {
        return [];
    }

    private function resolveSeoMeta(string $locale): ?SeoMeta
    {
        $rows = $this->relationLoaded('seoMeta') ? $this->getRelation('seoMeta') : $this->seoMeta()->get();

        return $rows->first(static fn (SeoMeta $meta): bool => $meta->locale === $locale)
            ?? $rows->first(static fn (SeoMeta $meta): bool => $meta->locale === null);
    }
}