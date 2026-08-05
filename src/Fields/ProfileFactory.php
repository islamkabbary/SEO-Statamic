<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fields;

use SilaSeo\Statamic\Locale\LocaleStrategy;
use SilaSeo\Statamic\Locale\MultisiteLocaleStrategy;
use SilaSeo\Statamic\Locale\PrefixLocaleStrategy;
use SilaSeo\Statamic\Locale\SingleSiteLocaleStrategy;

/**
 * Builds a project's field map and locale strategy from its config.
 *
 * The only place a profile name is turned into behaviour. Nothing downstream
 * knows which profile is active, and nothing anywhere branches on a project name.
 */
final class ProfileFactory
{
    /**
     * @param array<string, mixed> $config the `silaseo.statamic` config array
     */
    public function __construct(private readonly array $config)
    {
    }

    public function map(): FieldMap
    {
        return FieldMap::fromArray($this->profile());
    }

    /**
     * A misconfigured strategy degrades to single-site rather than guessing.
     * Emitting no hreflang is incomplete; emitting a wrong one misdirects a
     * crawler, and is the failure this whole layer exists to end.
     */
    public function localeStrategy(ValueReader $reader): LocaleStrategy
    {
        $locales = $this->locales();

        return match ($this->map()->localeStrategy()) {
            'prefix' => count($locales) >= 2
                ? new PrefixLocaleStrategy($locales, $reader)
                : new SingleSiteLocaleStrategy($this->fallbackLocale()),
            'multisite' => new MultisiteLocaleStrategy(),
            default => new SingleSiteLocaleStrategy($this->fallbackLocale()),
        };
    }

    public function resolver(ValueReader $reader): FieldResolver
    {
        return new FieldResolver($this->map(), $reader, $this->localeStrategy($reader));
    }

    /**
     * @return array<string, array{prefix?: string, hreflang?: string, x_default?: bool}>
     */
    public function locales(): array
    {
        $locales = $this->config['locales'] ?? [];

        if (! is_array($locales)) {
            return [];
        }

        $clean = [];

        foreach ($locales as $locale => $settings) {
            if (is_string($locale) && $locale !== '' && is_array($settings)) {
                $clean[$locale] = $settings;
            }
        }

        return $clean;
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(): array
    {
        $profiles = is_array($this->config['profiles'] ?? null) ? $this->config['profiles'] : [];
        $name = is_string($this->config['profile'] ?? null) ? $this->config['profile'] : 'native';

        foreach ([$name, 'native'] as $candidate) {
            if (is_array($profiles[$candidate] ?? null)) {
                return $profiles[$candidate];
            }
        }

        return [];
    }

    private function fallbackLocale(): string
    {
        $first = array_key_first($this->locales());

        if (is_string($first) && $first !== '') {
            return $first;
        }

        if (function_exists('app')) {
            try {
                return (string) app()->getLocale();
            } catch (\Throwable) {
                // Fall through.
            }
        }

        return 'en';
    }
}
