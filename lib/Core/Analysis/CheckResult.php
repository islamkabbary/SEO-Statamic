<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * The outcome of one check. Carries an i18n message key + params instead of a
 * baked string so the host can translate it (Arabic/English). Each result also
 * carries its own weight/priority/dimension so the {@see Scorer} is stateless.
 */
final class CheckResult
{
    /**
     * @param array<string,scalar> $params Values for the message-key placeholders.
     */
    public function __construct(
        public readonly string $id,
        public readonly Dimension $dimension,
        public readonly Priority $priority,
        public readonly int $weight,
        public readonly CheckStatus $status,
        public readonly string $messageKey,
        public readonly array $params = [],
    ) {
    }

    /**
     * @param array<string,scalar> $params
     */
    public static function make(
        string $id,
        Dimension $dimension,
        Priority $priority,
        int $weight,
        CheckStatus $status,
        array $params = [],
    ): self {
        return new self($id, $dimension, $priority, $weight, $status, "silaseo::analysis.{$id}.{$status->value}", $params);
    }
}