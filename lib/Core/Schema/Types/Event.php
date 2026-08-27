<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/Event.
 */
final class Event extends AbstractSchemaType
{
    public function __construct(string $name)
    {
        $this->set('name', $name);
    }

    public function type(): string
    {
        return 'Event';
    }

    public function startDate(?string $date): static
    {
        return $this->set('startDate', $date);
    }

    public function endDate(?string $date): static
    {
        return $this->set('endDate', $date);
    }

    public function description(?string $description): static
    {
        return $this->set('description', $description);
    }

    /**
     * @param array<string,string> $address PostalAddress fields.
     */
    public function location(string $name, array $address = []): static
    {
        return $this->set('location', [
            '@type' => 'Place',
            'name' => $name,
            'address' => $address === [] ? null : ['@type' => 'PostalAddress', ...$address],
        ]);
    }
}