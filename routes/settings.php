<?php

use App\Http\Controllers\Settings\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('akun/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('akun/password', [PasswordController::class, 'update'])->name('password.update');
});
