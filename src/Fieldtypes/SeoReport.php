<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Fieldtypes;

use SilaSeo\Core\Analysis\ContentAnalyzer;
use SilaSeo\Statamic\Analysis\EntryAnalysisFactory;
use SilaSeo\Statamic\Analysis\ReportPresenter;
use SilaSeo\Statamic\Support\AssetStrategy;
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
    /**
     * Rendered by a display-only core fieldtype when this addon's Vue components
     * cannot run. `section` exists in Statamic 4, 5 and 6 and renders nothing but
     * the field's display label.
     */
    private const FALLBACK_COMPONENT = 'section';

    protected $icon = 'seo';

    /**
     * This fieldtype is registered on every Statamic version, even where its Vue
     * component cannot load: FieldtypeRepository::find() throws
     * FieldtypeNotFoundException for an unknown handle, so leaving it out would
     * break the entire publish form of any blueprint importing the SEO fieldset,
     * rather than just omitting one panel.
     *
     * Instead the field falls back to a core component that exists in every
     * major, so the form renders and the remaining SEO fields stay editable.
     */
    public function component(): string
    {
        return AssetStrategy::current()->shipsVueComponents()
            ? parent::component()
            : self::FALLBACK_COMPONENT;
    }

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

        // Nothing renders the report when the Vue component cannot load, and a full
        // content analysis runs on every publish-form load. Skip the work.
        if (! AssetStrategy::current()->shipsVueComponents()) {
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