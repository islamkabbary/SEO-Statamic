<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use SilaSeo\Laravel\MetaService;

/**
 * Renders the full SEO <head> block. Drop `<x-seo-head />` into a layout's
 * <head>; the @seoHead directive is the lighter-weight alternative.
 */
final class Head extends Component
{
    public function __construct(private readonly MetaService $seo)
    {
    }

    public function render(): View
    {
        return view('silaseo::components.head', [
            'head' => $this->seo->head(),
        ]);
    }
}