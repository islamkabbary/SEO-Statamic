<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/Product with optional Offer and AggregateRating.
 */
final class Product extends AbstractSchemaType
{
    public function __construct(string $name)
    {
        $this->set('name', $name);
    }

    public function type(): string
    {
        return 'Product';
    }

    public function description(?string $description): static
    {
        return $this->set('description', $description);
    }

    /**
     * @param string|list<string>|null $image
     */
    public function image(string|array|null $image): static
    {
        return $this->set('image', $image);
    }

    public function sku(?string $sku): static
    {
        return $this->set('sku', $sku);
    }

    public function brand(?string $brand): static
    {
        return $this->set('brand', $brand === null ? null : ['@type' => 'Brand', 'name' => $brand]);
    }

    public function offer(int|float|string $price, string $currency, ?string $availability = null, ?string $url = null): static
    {
        return $this->set('offers', [
            '@type' => 'Offer',
            'price' => (string) $price,
            'priceCurrency' => $currency,
            'availability' => $availability === null ? null : 'https://schema.org/' . $availability,
            'url' => $url,
        ]);
    }

    public function aggregateRating(int|float|string $ratingValue, int $reviewCount): static
    {
        return $this->set('aggregateRating', [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $ratingValue,
            'reviewCount' => $reviewCount,
        ]);
    }
}