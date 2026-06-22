<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema;

/**
 * Aggregates JSON-LD nodes into a single `@graph` document, de-duplicating and
 * merging nodes that share an `@id` so entities are declared once.
 */
final class Graph
{
    /** @var list<array<string,mixed>> */
    private array $nodes = [];

    /**
     * @param list<SchemaNode|array<string,mixed>> $nodes
     */
    public static function fromNodes(array $nodes): self
    {
        $graph = new self();

        foreach ($nodes as $node) {
            $graph->add($node);
        }

        return $graph;
    }

    /**
     * @param SchemaNode|array<string,mixed> $node
     */
    public function add(SchemaNode|array $node): self
    {
        $this->nodes[] = $node instanceof SchemaNode ? $node->toArray() : Fallback::clean($node);

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => $this->merged(),
        ];
    }

    /**
     * Render as a JSON string. Slashes are escaped (default) so a value can
     * never break out of a surrounding <script> tag; Unicode is preserved so
     * Arabic content stays human-readable.
     */
    public function render(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Merge nodes sharing an @id (later properties win); id-less nodes are kept
     * as-is and appended after the keyed ones.
     *
     * @return list<array<string,mixed>>
     */
    private function merged(): array
    {
        $byId = [];
        $anonymous = [];

        foreach ($this->nodes as $node) {
            $id = $node['@id'] ?? null;

            if (! is_string($id) || $id === '') {
                $anonymous[] = $node;

                continue;
            }

            $byId[$id] = isset($byId[$id]) ? [...$byId[$id], ...$node] : $node;
        }

        return array_values([...$byId, ...$anonymous]);
    }
}