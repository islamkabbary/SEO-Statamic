<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema;

/**
 * A JSON-LD node that can be aggregated into the page {@see Graph}.
 */
interface SchemaNode
{
    /**
     * The schema.org @type (e.g. "Organization", "Article").
     */
    public function type(): string;

    /**
     * The node's @id when set, used for de-duplication across the graph.
     */
    public function id(): ?string;

    /**
     * The node as a JSON-LD associative array, cleaned of empty values.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array;
}