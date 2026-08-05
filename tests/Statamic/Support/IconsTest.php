<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\Support;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Support\Icons;

/**
 * Every Statamic major decides whether a nav icon is a name or literal markup with
 * the same check: Str::startsWith($value, '<svg').
 *
 * If a constant ever stops satisfying it, the value is treated as a *name* and
 * resolved against a version-specific icon directory. On Statamic 4 that happens
 * inside NavItem::icon()'s setter via Statamic::svg(), which calls File::get()
 * with no existence guard — so the miss is not a blank glyph, it is a
 * FileNotFoundException thrown while building the nav, which takes down every
 * Control Panel page. That is exactly the bug these constants replaced.
 */
final class IconsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function icons(): array
    {
        return [
            'redirects' => [Icons::REDIRECTS],
            'not found' => [Icons::NOT_FOUND],
        ];
    }

    /**
     * @dataProvider icons
     */
    public function test_it_is_taken_as_literal_markup_by_every_statamic_major(string $icon): void
    {
        // No leading whitespace: Str::startsWith is not trimmed on any version.
        self::assertStringStartsWith('<svg', $icon);
    }

    /**
     * @dataProvider icons
     */
    public function test_it_is_well_formed_and_survives_sanitisation(string $icon): void
    {
        self::assertStringEndsWith('</svg>', $icon);

        // NavItem::sanitizeSvg() strips scripts and event handlers on every major;
        // an icon relying on them would silently render broken.
        self::assertStringNotContainsStringIgnoringCase('<script', $icon);
        self::assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $icon);

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($icon);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        self::assertNotFalse($document, 'Icon is not parseable XML.');
    }

    /**
     * @dataProvider icons
     */
    public function test_it_inherits_the_control_panel_colour(string $icon): void
    {
        // A hardcoded colour would be invisible in one of the CP themes.
        self::assertStringContainsString('currentColor', $icon);
        self::assertDoesNotMatchRegularExpression('/(?:stroke|fill)="#/', $icon);
    }
}
