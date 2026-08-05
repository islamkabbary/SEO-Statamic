<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Controllers\Cp;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use SilaSeo\Statamic\NotFound\NotFoundLog;
use Statamic\Http\Controllers\CP\CpController;

/**
 * Control Panel 404 monitor: a read-only list of front-end 404s (path, hit
 * count, last seen) so the SEO specialist can spot broken links and add
 * redirects, with the option to clear the log.
 */
class NotFoundController extends CpController
{
    public function index(NotFoundLog $log): View
    {
        return view('silaseo::cp.notfound', ['entries' => $log->entries()]);
    }

    public function clear(NotFoundLog $log): RedirectResponse
    {
        $log->clear();

        return back()->with('success', __('silaseo::messages.notfound_cleared'));
    }
}