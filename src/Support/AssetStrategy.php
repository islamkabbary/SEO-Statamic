<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Support;

/**
 * How -- or whether -- this addon ships Control Panel JavaScript.
 *
 * The committed bundle is compiled against Statamic 6: it destructures
 * `__STATAMIC__.core` and pulls Vue 3 APIs off `window.Vue`. Statamic 4 and 5
 * both run Vue 2.7 and expose no `__STATAMIC__` global, so loading it there
 * throws before anything renders.
 *
 * Until a Vue 2 build exists there are only two honest outcomes, so there are
 * only two cases here. When that build lands, add a LegacyScript case and teach
 * {@see for()} to return it -- nothing else in the addon needs to change.
 */
enum AssetStrategy
{
    /** Statamic 6+: register the Vue 3 bundle through Vite. */
    case Vite;

    /** Statamic 4/5, or an undetermined version: ship no Control Panel JavaScript. */
    case None;

    public static function for(?int $major): self
    {
        return $major !== null && $major >= 6 ? self::Vite : self::None;
    }

    public static function current(): self
    {
        return self::for(StatamicVersion::major());
    }

    /**
     * Whether the Vue components this addon ships will actually run.
     *
     * Note this does NOT gate whether the fieldtype is registered. An unregistered
     * handle makes Statamic's FieldtypeRepository::find() throw
     * FieldtypeNotFoundException, so a blueprint importing the SEO fieldset would
     * take down the whole publish form -- far worse than a panel that renders
     * nothing. The fieldtype always registers; this only decides what it renders.
     */
    public function shipsVueComponents(): bool
    {
        return $this === self::Vite;
    }
}
