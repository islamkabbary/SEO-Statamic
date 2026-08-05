<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Support;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Support\Locale;

/**
 * hreflang values must be BCP 47 tags, whose subtags are hyphen-separated. The
 * package previously emitted whatever string it was handed, and what it was handed
 * was Statamic's site HANDLE -- so every single-site project in the fleet rendered
 * hreflang="default". Google ignores an invalid value silently, which makes a wrong
 * tag indistinguishable from no tag at all.
 */
final class LocaleHreflangTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function tags(): array
    {
        return [
            'bare language' => ['en', 'en'],
            'arabic' => ['ar', 'ar'],
            'three letter language' => ['fil', 'fil'],
            'underscored region becomes hyphenated' => ['en_US', 'en-US'],
            'already hyphenated' => ['en-US', 'en-US'],
            'lowercase region is uppercased' => ['en-us', 'en-US'],
            'uppercase language is lowercased' => ['EN', 'en'],
            'arabic with region' => ['ar_SA', 'ar-SA'],
            'egypt' => ['ar_EG', 'ar-EG'],
            'script subtag is titlecased' => ['zh_hans', 'zh-Hans'],
            'script and region' => ['zh_Hans_CN', 'zh-Hans-CN'],
            'numeric UN M.49 region' => ['es_419', 'es-419'],
            'whitespace is trimmed' => ['  en_GB  ', 'en-GB'],
            'the wildcard passes through' => ['x-default', 'x-default'],
            'the wildcard is case insensitive' => ['X-Default', 'x-default'],

            // The failure this exists to prevent.
            'a statamic site handle is not a language' => ['default', null],
            'a descriptive handle is not a language' => ['main_site', null],
            'a single letter is not a language' => ['e', null],
            'digits are not a language' => ['123', null],
            'empty' => ['', null],
            'whitespace only' => ['   ', null],
            'null' => [null, null],
        ];
    }

    /**
     * @dataProvider tags
     */
    public function test_it_normalises_or_rejects(?string $input, ?string $expected): void
    {
        self::assertSame($expected, Locale::hreflang($input));
    }

    public function test_it_never_emits_an_underscore(): void
    {
        foreach (['en_US', 'ar_SA', 'zh_Hans_CN', 'pt_BR'] as $input) {
            self::assertStringNotContainsString('_', (string) Locale::hreflang($input));
        }
    }

    public function test_it_is_the_inverse_of_the_open_graph_form(): void
    {
        // openGraph() normalises toward underscores, which is exactly wrong for
        // hreflang. Using it there was the original mistake; this pins them apart.
        self::assertSame('en_US', Locale::openGraph('en-US'));
        self::assertSame('en-US', Locale::hreflang('en_US'));
    }

    public function test_normalising_is_idempotent(): void
    {
        foreach (['en_US', 'ar', 'zh_hans_cn', 'x-default'] as $input) {
            $once = Locale::hreflang($input);
            self::assertSame($once, Locale::hreflang((string) $once));
        }
    }
}
