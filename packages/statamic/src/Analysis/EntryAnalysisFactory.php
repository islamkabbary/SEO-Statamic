<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Analysis;

use SilaSeo\Core\Analysis\AnalysisInput;
use Throwable;

/**
 * Builds an {@see AnalysisInput} from a Statamic entry, reading the SEO fields
 * plus a best-effort body text extraction.
 */
final class EntryAnalysisFactory
{
    /**
     * Build from a raw publish-form values array (used by the live CP analyze endpoint).
     *
     * @param array<string,mixed> $values
     */
    public function fromValues(array $values, string $locale = 'en', string $pageType = 'article'): AnalysisInput
    {
        return new AnalysisInput(
            focusKeyword: $this->arrayString($values, 'seo_focus_keyword'),
            title: $this->firstFilled($this->arrayString($values, 'seo_title'), $this->arrayString($values, 'title')),
            description: $this->firstFilled($this->arrayString($values, 'seo_description'), $this->arrayString($values, 'description')),
            slug: $this->arrayString($values, 'slug'),
            body: EntryTextExtractor::extract($values),
            locale: $locale,
            pageType: $pageType,
        );
    }

    public function fromEntry(object $entry, string $pageType = 'article'): AnalysisInput
    {
        return new AnalysisInput(
            focusKeyword: $this->string($entry, 'seo_focus_keyword'),
            title: $this->firstFilled($this->string($entry, 'seo_title'), $this->string($entry, 'title')),
            description: $this->firstFilled($this->string($entry, 'seo_description'), $this->string($entry, 'description')),
            slug: $this->slug($entry),
            body: $this->body($entry),
            locale: $this->locale($entry),
            pageType: $pageType,
        );
    }

    private function string(object $entry, string $handle): string
    {
        try {
            $value = $entry->value($handle);

            return is_string($value) ? $value : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function firstFilled(string ...$values): string
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param array<string,mixed> $values
     */
    private function arrayString(array $values, string $key): string
    {
        return is_string($values[$key] ?? null) ? $values[$key] : '';
    }

    private function slug(object $entry): string
    {
        try {
            return method_exists($entry, 'slug') ? (string) $entry->slug() : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function locale(object $entry): string
    {
        try {
            return method_exists($entry, 'locale') ? (string) $entry->locale() : 'en';
        } catch (Throwable) {
            return 'en';
        }
    }

    private function body(object $entry): string
    {
        try {
            $data = method_exists($entry, 'data') ? $entry->data()->all() : [];

            return EntryTextExtractor::extract(is_array($data) ? $data : []);
        } catch (Throwable) {
            return '';
        }
    }
}