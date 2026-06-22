<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SilaSeo\Statamic\Http\Controllers\IndexNowKeyController;
use SilaSeo\Statamic\Http\Controllers\RobotsController;

Route::get('robots.txt', RobotsController::class)->name('silaseo.robots');

// IndexNow key verification file. The 8–128 char constraint keeps it from
// shadowing shorter paths (e.g. robots.txt); a non-matching key 404s.
Route::get('{key}.txt', IndexNowKeyController::class)
    ->where('key', '[A-Za-z0-9\-]{8,128}')
    ->name('silaseo.indexnow-key');