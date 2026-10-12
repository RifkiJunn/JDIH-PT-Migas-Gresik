<?php

use App\Http\Controllers\DokumenController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\PersetujuanController;
use Illuminate\Support\Facades\Route;

// Terbuka untuk umum. Hak lihat tiap dokumen diperiksa DocumentPolicy di dalam controller.
Route::get('/', [DokumenController::class, 'beranda'])->name('beranda');
Route::get('dokumen/{document}', [DokumenController::class, 'show'])->name('dokumen.show');
Route::get('dokumen/{document}/berkas', [DokumenController::class, 'lihatBerkas'])->name('berkas.lihat');
Route::get('dokumen/{document}/berkas/unduh', [DokumenController::class, 'unduhBerkas'])->name('berkas.unduh');

Route::middleware('auth')->group(function () {
    Route::get('ruang', fn () => redirect(request()->user()->beranda()))->name('ruang');

    Route::get('arsip', [DokumenController::class, 'arsip'])->middleware('peran:karyawan,manajer,gm')->name('arsip.index');

    Route::middleware('peran:karyawan,manajer')->group(function () {
        Route::get('pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
        Route::get('pengajuan/buat', [PengajuanController::class, 'create'])->name('pengajuan.create');
        Route::post('pengajuan', [PengajuanController::class, 'store'])->name('pengajuan.store');
        Route::get('pengajuan/{document}/ubah', [PengajuanController::class, 'edit'])->name('pengajuan.edit');
        Route::put('pengajuan/{document}', [PengajuanController::class, 'update'])->name('pengajuan.update');
        Route::delete('pengajuan/{document}', [PengajuanController::class, 'destroy'])->name('pengajuan.destroy');
        Route::post('pengajuan/{document}/ajukan', [PengajuanController::class, 'ajukan'])->name('pengajuan.ajukan');
    });

    Route::middleware('peran:manajer,gm')->group(function () {
        Route::get('persetujuan', [PersetujuanController::class, 'index'])->name('persetujuan.index');
        Route::get('persetujuan/{document}', [PersetujuanController::class, 'show'])->name('persetujuan.show');
        Route::post('persetujuan/{document}/setujui', [PersetujuanController::class, 'setujui'])->name('persetujuan.setujui');
        Route::post('persetujuan/{document}/tolak', [PersetujuanController::class, 'tolak'])->name('persetujuan.tolak');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
