<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema;

/**
 * Validates JSON-LD nodes against the required properties Google expects for
 * each supported type, so the toolkit never ships rich-result-breaking schema.
 */
final class Validator
{
    /**
     * Required properties per @type.
     *
     * @var array<string, list<string>>
     */
    private const REQUIRED = [
        'Organization' => ['name', 'url'],
        'LocalBusiness' => ['name', 'url'],
        'WebSite' => ['url'],
        'Article' => ['headline'],
        'BlogPosting' => ['headline'],
        'NewsArticle' => ['headline'],
        'BreadcrumbList' => ['itemListElement'],
        'Product' => ['name'],
        'Person' => ['name'],
        'Course' => ['name'],
        'FAQPage' => ['mainEntity'],
        'VideoObject' => ['name', 'thumbnailUrl', 'uploadDate'],
        'HowTo' => ['name', 'step'],
        'Event' => ['name', 'startDate'],
    ];

    /**
     * @param SchemaNode|array<string,mixed> $node
     *
     * @return list<ValidationIssue>
     */
    public function validate(SchemaNode|array $node): array
    {
        $node = $node instanceof SchemaNode ? $node->toArray() : $node;
        $type = $node['@type'] ?? null;

        if (! is_string($type) || $type === '') {
            return [new ValidationIssue('(unknown)', '@type', 'Node is missing an @type.')];
        }

        $issues = [];

        foreach (self::REQUIRED[$type] ?? [] as $property) {
            if ($this->isMissing($node[$property] ?? null)) {
                $issues[] = new ValidationIssue(
                    $type,
                    $property,
                    sprintf("Missing required property '%s' for %s.", $property, $type),
                );
            }
        }

        return $issues;
    }

    /**
     * @param SchemaNode|array<string,mixed> $node
     */
    public function isValid(SchemaNode|array $node): bool
    {
        return $this->validate($node) === [];
    }

    private function isMissing(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}