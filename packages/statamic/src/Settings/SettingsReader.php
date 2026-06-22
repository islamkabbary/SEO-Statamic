<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Settings;

use SilaSeo\Laravel\MetaService;
use Statamic\Facades\GlobalSet;
use Throwable;

/**
 * Reads the `seo_settings` Statamic global (per current site) and applies it to
 * the request-scoped {@see MetaService}: cascade defaults + the site-wide
 * Organization/WebSite used for JSON-LD. Degrades silently if the global is
 * absent, so projects without it fall back to config.
 */
final class SettingsReader
{
    public function applyTo(MetaService $seo): void
    {
        $data = $this->data();

        if ($data === []) {
            return;
        }

        $defaults = array_filter([
            'site_name' => $this->string($data, 'org_name'),
            'title_pattern' => $this->string($data, 'title_pattern'),
            'image' => $this->string($data, 'default_og_image'),
            'twitter_site' => $this->string($data, 'twitter_handle'),
        ], static fn (string $value): bool => $value !== '');

        if ($defaults !== []) {
            $seo->defaults($defaults);
        }

        if (($gsc = $this->string($data, 'gsc_verification')) !== '') {
            $seo->meta('google-site-verification', $gsc);
        }

        if (($bing = $this->string($data, 'bing_verification')) !== '') {
            $seo->meta('msvalidate.01', $bing);
        }

        $name = $this->string($data, 'org_name');

        if ($name === '') {
            return;
        }

        $seo->organization(array_filter([
            'name' => $name,
            'url' => $this->string($data, 'org_url') ?: rtrim((string) config('app.url'), '/'),
            'logo' => $this->string($data, 'org_logo'),
            'email' => $this->string($data, 'org_email'),
            'telephone' => $this->string($data, 'org_phone'),
            'same_as' => $this->list($data, 'same_as'),
        ], static fn (mixed $value): bool => $value !== '' && $value !== [] && $value !== null));

        $seo->website(['enabled' => true, 'search_url' => $this->string($data, 'search_url') ?: null]);
    }

    /**
     * @return array<string,mixed>
     */
    private function data(): array
    {
        try {
            $set = GlobalSet::find('seo_settings');

            return $set ? (array) $set->inCurrentSite()->data()->all() : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function string(array $data, string $key): string
    {
        return is_string($data[$key] ?? null) ? trim($data[$key]) : '';
    }

    /**
     * @param array<string,mixed> $data
     *
     * @return list<string>
     */
    private function list(array $data, string $key): array
    {
        if (! is_array($data[$key] ?? null)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $v): string => is_string($v) ? trim($v) : '',
            $data[$key],
        )));
    }
}