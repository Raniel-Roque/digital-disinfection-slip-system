<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view("/", "landing");

Route::get("/login", [SessionController::class,'create'])->name('login');
Route::post('/login', [SessionController::class,'store'])->name('login.store');

// Location-based login routes
Route::get("/location/{location}/login", [SessionController::class,'create'])->name('location.login');
Route::post('/location/{location}/login', [SessionController::class,'store'])->name('location.login.store');

Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

// Password change routes (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [App\Http\Controllers\PasswordController::class, 'show'])->name('password.change');
    Route::post('/password/verify', [App\Http\Controllers\PasswordController::class, 'verify'])->name('password.verify');
    Route::put('/password', [App\Http\Controllers\PasswordController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'user.type:0'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/incoming-trucks', [UserController::class, 'incomingTrucks'])->name('incoming-trucks');
    Route::get('/outgoing-trucks', [UserController::class, 'outgoingTrucks'])->name('outgoing-trucks');
    Route::get('/completed-trucks', [UserController::class, 'completedTrucks'])->name('completed-trucks');
});

Route::middleware(['auth', 'user.type:1'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/guards', [AdminController::class, 'guards'])->name('guards');
    Route::get('/drivers', [AdminController::class, 'drivers'])->name('drivers');
    Route::get('/locations', [AdminController::class, 'locations'])->name('locations');
    Route::get('/plate-numbers', [AdminController::class, 'plateNumbers'])->name('plate-numbers');
    Route::get('/trucks', [AdminController::class, 'trucks'])->name('trucks');
});

Route::middleware(['auth', 'user.type:2'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/guards', [SuperAdminController::class, 'guards'])->name('guards');
    Route::get('/admins', [SuperAdminController::class, 'admins'])->name('admins');
    Route::get('/drivers', [SuperAdminController::class, 'drivers'])->name('drivers');
    Route::get('/locations', [SuperAdminController::class, 'locations'])->name('locations');
    Route::get('/plate-numbers', [SuperAdminController::class, 'plateNumbers'])->name('plate-numbers');
    Route::get('/trucks', [SuperAdminController::class, 'trucks'])->name('trucks');
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings');
});

