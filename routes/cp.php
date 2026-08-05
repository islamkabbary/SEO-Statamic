<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SilaSeo\Statamic\Http\Controllers\Cp\AnalyzeController;
use SilaSeo\Statamic\Http\Controllers\Cp\LinkSuggestionsController;
use SilaSeo\Statamic\Http\Controllers\Cp\NotFoundController;
use SilaSeo\Statamic\Http\Controllers\Cp\RedirectsController;

Route::post('silaseo/analyze', AnalyzeController::class)->name('silaseo.analyze');
Route::post('silaseo/link-suggestions', LinkSuggestionsController::class)->name('silaseo.link-suggestions');
Route::get('silaseo/404', [NotFoundController::class, 'index'])->name('silaseo.404-log');
Route::delete('silaseo/404', [NotFoundController::class, 'clear'])->name('silaseo.404-log.clear');
Route::get('silaseo/redirects', [RedirectsController::class, 'index'])->name('silaseo.redirects');
Route::get('silaseo/redirects/export', [RedirectsController::class, 'export'])->name('silaseo.redirects.export');
Route::post('silaseo/redirects/import', [RedirectsController::class, 'import'])->name('silaseo.redirects.import');