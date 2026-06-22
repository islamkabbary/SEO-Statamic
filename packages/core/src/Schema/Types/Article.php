<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/Article — base for editorial content. {@see BlogPosting} and
 * {@see NewsArticle} extend it with a narrower @type.
 */
class Article extends AbstractSchemaType
{
    public function __construct(string $headline)
    {
        $this->set('headline', $headline);
    }

    public function type(): string
    {
        return 'Article';
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

    public function datePublished(?string $date): static
    {
        return $this->set('datePublished', $date);
    }

    public function dateModified(?string $date): static
    {
        return $this->set('dateModified', $date);
    }

    public function author(?string $name, ?string $url = null): static
    {
        if ($name === null || $name === '') {
            return $this;
        }

        return $this->set('author', ['@type' => 'Person', 'name' => $name, 'url' => $url]);
    }

    public function publisherId(?string $id): static
    {
        return $this->set('publisher', $id === null ? null : ['@id' => $id]);
    }

    public function mainEntityOfPage(?string $url): static
    {
        return $this->set('mainEntityOfPage', $url);
    }
}