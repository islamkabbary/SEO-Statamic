<?php

declare(strict_types=1);

namespace SilaSeo\Statamic;

use SilaSeo\Core\Schema\Types\Article;
use SilaSeo\Core\Schema\Types\BlogPosting;

/**
 * Pure mapping from a Statamic entry's extracted SEO field values to a cascade
 * payload (+ JSON-LD). Framework-agnostic and unit-testable without booting
 * Statamic; the version-specific value extraction lives in the gateway drivers.
 */
final class EntryMapper
{
    /**
     * @param array<string,mixed> $fields  seo_title, seo_description, seo_image(url), seo_canonical, noindex(bool), schema_json(raw JSON-LD string)
     * @param array<string,mixed> $context fallback_title, fallback_image, url, locale, schema_type
     *
     * @return array<string,mixed>
     */
    public function toPayload(array $fields, array $context): array
    {
        $title = $this->firstFilled($fields['seo_title'] ?? null, $context['fallback_title'] ?? null);
        $description = $this->trimToNull($fields['seo_description'] ?? null);
        $image = $this->firstFilled($fields['seo_image'] ?? null, $context['fallback_image'] ?? null);
        $canonical = $this->trimToNull($fields['seo_canonical'] ?? null);

        $payload = [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'canonical' => $canonical,
            'og_type' => 'article',
        ];

        if (! empty($fields['noindex'])) {
            $payload['robots'] = ['noindex', 'follow'];
        }

        $schema = $this->buildSchemaGraph($fields, $context, $title, $description, $image);

        if ($schema !== []) {
            $payload['schema'] = $schema;
        }

        return array_filter($payload, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * The entry's JSON-LD: the auto-mapped typed node (when a title exists) plus
     * any raw JSON-LD the editor supplied as an override.
     *
     * @param array<string,mixed> $fields
     * @param array<string,mixed> $context
     *
     * @return list<array<string,mixed>>
     */
    private function buildSchemaGraph(array $fields, array $context, ?string $title, ?string $description, ?string $image): array
    {
        $raw = $this->parseRawSchema($fields['schema_json'] ?? null);

        if ($raw !== []) {
            return $raw;
        }

        if ($title !== null) {
            return [$this->buildSchema((string) ($context['schema_type'] ?? 'Article'), $title, $description, $image, $context)];
        }

        return [];
    }

    /**
     * Decode an editor-supplied raw JSON-LD override into one or more nodes. A
     * single object, a bare array of objects, or a `@graph` wrapper are accepted;
     * invalid JSON is ignored so a typo never breaks the page head.
     *
     * @return list<array<string,mixed>>
     */
    private function parseRawSchema(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            $decoded = $decoded['@graph'];
        }

        $nodes = array_is_list($decoded) ? $decoded : [$decoded];

        return array_values(array_filter(
            $nodes,
            static fn (mixed $node): bool => is_array($node) && isset($node['@type']),
        ));
    }

    /**
     * @param array<string,mixed> $context
     *
     * @return array<string,mixed>
     */
    private function buildSchema(string $type, string $title, ?string $description, ?string $image, array $context): array
    {
        $article = $type === 'BlogPosting' ? new BlogPosting($title) : new Article($title);

        return $article
            ->description($description)
            ->image($image)
            ->inLanguage($this->trimToNull($context['locale'] ?? null))
            ->mainEntityOfPage($this->trimToNull($context['url'] ?? null))
            ->toArray();
    }

    private function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $clean = $this->trimToNull($value);

            if ($clean !== null) {
                return $clean;
            }
        }

        return null;
    }

    private function trimToNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}