<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/LocalBusiness — an Organization with a physical presence.
 */
final class LocalBusiness extends Organization
{
    public function type(): string
    {
        return 'LocalBusiness';
    }

    /**
     * @param array<string,string> $address PostalAddress fields
     *                                       (streetAddress, addressLocality, addressRegion,
     *                                       postalCode, addressCountry).
     */
    public function address(array $address): static
    {
        return $this->set('address', ['@type' => 'PostalAddress', ...$address]);
    }

    public function priceRange(?string $priceRange): static
    {
        return $this->set('priceRange', $priceRange);
    }
}