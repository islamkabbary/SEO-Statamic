<?php

declare(strict_types=1);

namespace SilaSeo\Statamic;

use SilaSeo\Laravel\MetaService;
use SilaSeo\Statamic\Gateway\StatamicGateway;

/**
 * Applies a Statamic entry's SEO (fields + JSON-LD + hreflang) to the
 * request-scoped {@see MetaService}, so a Blade- or Antlers-rendered entry page
 * gets the same head as any other page type.
 */
final class EntrySeo
{
    public function __construct(
        private readonly StatamicGateway $gateway,
        private readonly EntryMapper $mapper,
        private readonly MetaService $seo,
    ) {
    }

    public function apply(object $entry, string $schemaType = 'Article'): void
    {
        $data = $this->gateway->extract($entry, $schemaType);

        $this->seo->for($this->mapper->toPayload($data['fields'], $data['context']));

        foreach ($data['alternates'] as $alternate) {
            $this->seo->alternate($alternate['hreflang'], $alternate['url']);
        }
    }
}