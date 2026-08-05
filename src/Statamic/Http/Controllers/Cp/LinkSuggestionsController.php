<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Controllers\Cp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SilaSeo\Core\Link\LinkContent;
use SilaSeo\Core\Link\LinkSuggester;
use SilaSeo\Core\Link\LinkSuggestion;
use SilaSeo\Statamic\Analysis\EntryTextExtractor;
use SilaSeo\Statamic\Link\EntryLinkCorpus;
use Statamic\Facades\Entry;
use Statamic\Http\Controllers\CP\CpController;
use Throwable;

/**
 * Internal-linking suggestions endpoint for the Control Panel. Scans the entry
 * body being edited for other published entries' keyphrases and returns the most
 * relevant link targets. CP-authenticated (extends CpController).
 */
class LinkSuggestionsController extends CpController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'values' => ['nullable', 'array'],
            'locale' => ['nullable', 'string'],
            'id' => ['nullable', 'string'],
        ]);

        $id = (string) ($data['id'] ?? '');

        $content = new LinkContent(
            EntryTextExtractor::extract($data['values'] ?? []),
            $data['locale'] ?? 'en',
            $id,
            $this->currentUrl($id),
        );

        $suggestions = (new LinkSuggester())->suggest($content, (new EntryLinkCorpus())->targets());

        return response()->json([
            'suggestions' => array_map(static fn (LinkSuggestion $s): array => $s->toArray(), $suggestions),
        ]);
    }

    private function currentUrl(string $id): string
    {
        if ($id === '') {
            return '';
        }

        try {
            $url = Entry::find($id)?->url();

            return is_string($url) ? $url : '';
        } catch (Throwable) {
            return '';
        }
    }
}