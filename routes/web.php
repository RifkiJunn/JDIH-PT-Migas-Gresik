<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('beranda'))->name('beranda');

Route::middleware('auth')->group(function () {
    Route::get('ruang', fn () => redirect(request()->user()->beranda()))->name('ruang');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
