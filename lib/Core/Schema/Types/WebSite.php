<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/WebSite — the site itself, optionally exposing a sitelinks
 * search box via a SearchAction.
 */
final class WebSite extends AbstractSchemaType
{
    public function __construct(string $name, string $url)
    {
        $this->set('name', $name)->set('url', $url);
    }

    public function type(): string
    {
        return 'WebSite';
    }

    /**
     * Declare the sitelinks search box.
     *
     * @param string $urlTemplate e.g. "https://example.com/search?q={search_term_string}"
     */
    public function searchAction(string $urlTemplate, string $queryInput = 'required name=search_term_string'): static
    {
        return $this->set('potentialAction', [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $urlTemplate,
            ],
            'query-input' => $queryInput,
        ]);
    }

    public function publisherId(?string $id): static
    {
        return $this->set('publisher', $id === null ? null : ['@id' => $id]);
    }
}