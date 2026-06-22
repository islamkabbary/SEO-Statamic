<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/Course.
 */
final class Course extends AbstractSchemaType
{
    public function __construct(string $name)
    {
        $this->set('name', $name);
    }

    public function type(): string
    {
        return 'Course';
    }

    public function description(?string $description): static
    {
        return $this->set('description', $description);
    }

    public function provider(?string $name, ?string $url = null): static
    {
        if ($name === null || $name === '') {
            return $this;
        }

        return $this->set('provider', ['@type' => 'Organization', 'name' => $name, 'url' => $url]);
    }
}