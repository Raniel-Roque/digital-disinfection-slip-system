<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view("/", "landing");

Route::get("/login", [SessionController::class,'create'])->name('login');
Route::post('/login', [SessionController::class,'store'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    Route::get('/user/dashboard', [DashboardController::class, 'user'])
        ->middleware('user.type:0')
        ->name('user.dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('user.type:1')
        ->name('admin.dashboard');

    Route::get('/superadmin/dashboard', [DashboardController::class, 'superadmin'])
        ->middleware('user.type:2')
        ->name('superadmin.dashboard');

    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});