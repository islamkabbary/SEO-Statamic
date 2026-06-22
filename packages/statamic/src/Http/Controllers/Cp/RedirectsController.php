<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Controllers\Cp;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SilaSeo\Statamic\Redirects\GlobalRedirectStore;
use SilaSeo\Statamic\Redirects\RedirectCsv;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control Panel bulk import/export for redirect rules. Editing individual rules
 * still happens in the seo_settings global grid; this screen adds CSV round-trip
 * for managing many redirects at once.
 */
class RedirectsController extends CpController
{
    public function index(GlobalRedirectStore $store): View
    {
        return view('silaseo::cp.redirects', ['redirects' => $store->all()]);
    }

    public function export(GlobalRedirectStore $store, RedirectCsv $csv): Response
    {
        return response($csv->export($store->all()), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="redirects.csv"',
        ]);
    }

    public function import(Request $request, GlobalRedirectStore $store, RedirectCsv $csv): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:2048']]);

        $rows = $csv->import((string) file_get_contents($request->file('file')->getRealPath()));
        $added = $store->merge($rows);

        return back()->with('success', __('silaseo::messages.redirects_imported', [
            'added' => $added,
            'total' => count($rows),
        ]));
    }
}