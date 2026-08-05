<?php

declare(strict_types=1);

namespace SilaSeo\Core\Meta;

use SilaSeo\Core\Support\Html;

/**
 * An immutable representation of a single <head> element: a <title>, a
 * <meta name>, a <meta property> (Open Graph), or a <link>. Rendering is
 * centralised here so every delivery path emits byte-identical output.
 */
final class MetaTag
{
    private const TITLE = 'title';
    private const META_NAME = 'meta_name';
    private const META_PROPERTY = 'meta_property';
    private const LINK = 'link';

    /**
     * @param array<string, string|null> $attributes
     */
    private function __construct(
        private readonly string $type,
        private readonly array $attributes = [],
        private readonly ?string $text = null,
    ) {
    }

    public static function title(string $text): self
    {
        return new self(self::TITLE, text: $text);
    }

    public static function name(string $name, string $content): self
    {
        return new self(self::META_NAME, ['name' => $name, 'content' => $content]);
    }

    public static function property(string $property, string $content): self
    {
        return new self(self::META_PROPERTY, ['property' => $property, 'content' => $content]);
    }

    /**
     * @param array<string, string|null> $extra
     */
    public static function link(string $rel, string $href, array $extra = []): self
    {
        return new self(self::LINK, ['rel' => $rel, 'href' => $href, ...$extra]);
    }

    public function render(): string
    {
        return match ($this->type) {
            self::TITLE => '<title>' . Html::attr((string) $this->text) . '</title>',
            self::LINK => '<link' . Html::attributes($this->attributes) . '>',
            default => '<meta' . Html::attributes($this->attributes) . '>',
        };
    }
}