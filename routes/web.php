<?php

use App\Http\Controllers\PengajuanController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('beranda'))->name('beranda');

Route::middleware('auth')->group(function () {
    Route::get('ruang', fn () => redirect(request()->user()->beranda()))->name('ruang');

    Route::middleware('peran:karyawan,manajer')->group(function () {
        Route::get('pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
        Route::get('pengajuan/buat', [PengajuanController::class, 'create'])->name('pengajuan.create');
        Route::post('pengajuan', [PengajuanController::class, 'store'])->name('pengajuan.store');
        Route::get('pengajuan/{document}/ubah', [PengajuanController::class, 'edit'])->name('pengajuan.edit');
        Route::put('pengajuan/{document}', [PengajuanController::class, 'update'])->name('pengajuan.update');
        Route::delete('pengajuan/{document}', [PengajuanController::class, 'destroy'])->name('pengajuan.destroy');
        Route::post('pengajuan/{document}/ajukan', [PengajuanController::class, 'ajukan'])->name('pengajuan.ajukan');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
