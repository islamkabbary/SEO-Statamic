<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fieldtypes;

use SilaSeo\Core\Analysis\ContentAnalyzer;
use SilaSeo\Statamic\Analysis\EntryAnalysisFactory;
use SilaSeo\Statamic\Analysis\ReportPresenter;
use Statamic\Fields\Fieldtype;
use Throwable;

/**
 * A read-only Control Panel panel that shows the live SEO analysis (score +
 * checklist) for the entry being edited. The analysis is computed server-side
 * by the core engine in preload(); the Vue component only renders it.
 *
 * Stores no value of its own.
 */
class SeoReport extends Fieldtype
{
    protected $icon = 'seo';

    /**
     * @return array{report: array<string,mixed>|null, endpoint: string, link_endpoint: string, locale: string, page_type: string, site_url: string}
     */
    public function preload(): array
    {
        $cpRoute = trim((string) config('statamic.cp.route', 'cp'), '/');

        $meta = [
            'report' => null,
            'endpoint' => url($cpRoute . '/silaseo/analyze'),
            'link_endpoint' => url($cpRoute . '/silaseo/link-suggestions'),
            'locale' => 'en',
            'page_type' => 'article',
            'site_url' => rtrim((string) config('app.url'), '/'),
        ];

        $entry = $this->field?->parent();

        if ($entry === null || ! is_object($entry) || ! method_exists($entry, 'value')) {
            return $meta;
        }

        try {
            $input = (new EntryAnalysisFactory())->fromEntry($entry);
            $meta['locale'] = $input->locale;
            $meta['report'] = (new ReportPresenter())->present((new ContentAnalyzer())->analyze($input), $input->locale);
        } catch (Throwable) {
            // Leave report null on any failure.
        }

        return $meta;
    }

    public function preProcess($data): mixed
    {
        return $data;
    }

    public function process($data): mixed
    {
        return null;
    }

    public function augment($value): mixed
    {
        return null;
    }
}