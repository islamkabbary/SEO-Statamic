<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

use SilaSeo\Core\Schema\Fallback;
use SilaSeo\Core\Schema\SchemaNode;

/**
 * Base for JSON-LD type builders. Concrete types set their properties via the
 * protected {@see set()} helper; output is cleaned of empty values on render.
 */
abstract class AbstractSchemaType implements SchemaNode
{
    /** @var array<string,mixed> */
    protected array $properties = [];

    protected ?string $id = null;

    abstract public function type(): string;

    public function id(): ?string
    {
        return $this->id;
    }

    public function withId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function inLanguage(?string $language): static
    {
        return $this->set('inLanguage', $language);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $head = ['@type' => $this->type()];

        if ($this->id !== null) {
            $head['@id'] = $this->id;
        }

        return [...$head, ...Fallback::clean($this->properties)];
    }

    protected function set(string $key, mixed $value): static
    {
        $this->properties[$key] = $value;

        return $this;
    }
}