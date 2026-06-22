<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/BreadcrumbList — the page's position in the site hierarchy.
 */
final class BreadcrumbList extends AbstractSchemaType
{
    public function type(): string
    {
        return 'BreadcrumbList';
    }

    /**
     * Append a crumb; positions are assigned automatically in insertion order.
     */
    public function add(string $name, string $url): static
    {
        /** @var list<array<string,mixed>> $items */
        $items = $this->properties['itemListElement'] ?? [];

        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $name,
            'item' => $url,
        ];

        return $this->set('itemListElement', $items);
    }

    /**
     * @param array<string,string> $crumbs name => url, in order.
     */
    public function fromPairs(array $crumbs): static
    {
        foreach ($crumbs as $name => $url) {
            $this->add($name, $url);
        }

        return $this;
    }
}