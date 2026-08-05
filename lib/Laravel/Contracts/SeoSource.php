<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Contracts;

/**
 * A model or object that can describe its own SEO as a cascade payload.
 * Implemented by Eloquent models via the {@see \SilaSeo\Laravel\Concerns\HasSeo} trait.
 */
interface SeoSource
{
    /**
     * @return array<string,mixed> Cascade payload (title, description, image, canonical, robots, schema, ...).
     */
    public function toSeoPayload(?string $locale = null): array;
}