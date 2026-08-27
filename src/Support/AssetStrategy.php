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
 * So there are three outcomes: Statamic 6+ loads the Vue 3 bundle through Vite;
 * Statamic 4/5 load a pre-built Vue 2.7 bundle -- the same components compiled for the
 * 2.7 Control Panel -- as a plain script; an undetermined version ships nothing, because
 * guessing wrong would load an incompatible bundle and break the panel before it renders.
 */
enum AssetStrategy
{
    /** Statamic 6+: register the Vue 3 bundle through Vite. */
    case Vite;

    /** Statamic 4/5: ship the pre-built Vue 2.7 bundle as a plain Control Panel script. */
    case LegacyScript;

    /** An undetermined version: ship no Control Panel JavaScript. */
    case None;

    public static function for(?int $major): self
    {
        if ($major === null) {
            return self::None;
        }

        if ($major >= 6) {
            return self::Vite;
        }

        return $major === 4 || $major === 5 ? self::LegacyScript : self::None;
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
        return $this === self::Vite || $this === self::LegacyScript;
    }
}
