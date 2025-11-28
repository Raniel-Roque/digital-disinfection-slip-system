<?php

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get("/", [SessionController::class,'create'])->name('login');
Route::post('/', [SessionController::class,'store']);

Route::get('/home', function () {
    return view('home');
})->middleware('auth');
