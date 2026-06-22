<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Routing;

/**
 * Fluent builder for registering SEO against a route name or URI.
 * Each setter flushes the accumulated payload into the {@see SeoRegistry}.
 */
final class PendingSeo
{
    /** @var array<string,mixed> */
    private array $payload = [];

    public function __construct(
        private readonly SeoRegistry $registry,
        private readonly string $key,
    ) {
    }

    public function title(string $title): self
    {
        return $this->set('title', $title);
    }

    public function description(string $description): self
    {
        return $this->set('description', $description);
    }

    public function image(string $image): self
    {
        return $this->set('image', $image);
    }

    public function canonical(string $canonical): self
    {
        return $this->set('canonical', $canonical);
    }

    /**
     * @param list<string>|string $robots
     */
    public function robots(array|string $robots): self
    {
        return $this->set('robots', $robots);
    }

    public function noindex(): self
    {
        return $this->set('robots', ['noindex', 'follow']);
    }

    /**
     * @param array<string,mixed> $node
     */
    public function schema(array $node): self
    {
        $this->payload['schema'][] = $node;

        return $this->flush();
    }

    private function set(string $key, mixed $value): self
    {
        $this->payload[$key] = $value;

        return $this->flush();
    }

    private function flush(): self
    {
        $this->registry->register($this->key, $this->payload);

        return $this;
    }
}