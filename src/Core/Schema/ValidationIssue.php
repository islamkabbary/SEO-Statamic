<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema;

/**
 * A single problem found while validating a JSON-LD node.
 */
final class ValidationIssue
{
    public const ERROR = 'error';
    public const WARNING = 'warning';

    public function __construct(
        public readonly string $type,
        public readonly string $property,
        public readonly string $message,
        public readonly string $severity = self::ERROR,
    ) {
    }

    public function isError(): bool
    {
        return $this->severity === self::ERROR;
    }
}