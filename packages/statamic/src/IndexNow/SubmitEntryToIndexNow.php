<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\IndexNow;

use Throwable;

/**
 * Submits a saved or deleted entry's URL to IndexNow when a key is configured.
 * Disabled (no-op) until an IndexNow key is set in the SEO Settings global.
 */
final class SubmitEntryToIndexNow
{
    public function __construct(
        private readonly IndexNowConfig $config,
        private readonly IndexNowPinger $pinger,
    ) {
    }

    public function handle(object $event): void
    {
        $key = $this->config->key();

        if ($key === '') {
            return;
        }

        $entry = $event->entry ?? null;

        if (! is_object($entry) || ! method_exists($entry, 'absoluteUrl')) {
            return;
        }

        try {
            $url = (string) $entry->absoluteUrl();
        } catch (Throwable) {
            return;
        }

        $target = IndexNowSubmission::for($url, $key);

        if ($target === null) {
            return;
        }

        $this->pinger->submit([$target['url']], $key, $target['host'], $target['keyLocation']);
    }
}