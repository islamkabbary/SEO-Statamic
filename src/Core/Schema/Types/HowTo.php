<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/HowTo — an ordered set of steps.
 */
final class HowTo extends AbstractSchemaType
{
    public function __construct(string $name)
    {
        $this->set('name', $name);
    }

    public function type(): string
    {
        return 'HowTo';
    }

    public function description(?string $description): static
    {
        return $this->set('description', $description);
    }

    public function step(string $name, string $text, ?string $url = null): static
    {
        /** @var list<array<string,mixed>> $steps */
        $steps = $this->properties['step'] ?? [];

        $steps[] = [
            '@type' => 'HowToStep',
            'name' => $name,
            'text' => $text,
            'url' => $url,
        ];

        return $this->set('step', $steps);
    }
}