<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\IndexNow;

use Statamic\Facades\GlobalSet;
use Throwable;

/**
 * Reads the IndexNow key from the `seo_settings` global. An empty key means the
 * integration is disabled — nothing is served or submitted until a key is set.
 */
final class IndexNowConfig
{
    public function key(): string
    {
        try {
            $set = GlobalSet::find('seo_settings');
            $value = $set ? $set->inCurrentSite()->data()->get('indexnow_key') : null;

            return is_string($value) ? trim($value) : '';
        } catch (Throwable) {
            return '';
        }
    }

    public function enabled(): bool
    {
        return $this->key() !== '';
    }
}