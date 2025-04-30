<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;



Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/fetch-climate-data', [DashboardController::class, 'fetchClimateData'])->name('fetch.climate.data');
    Route::get('/download-pdf', [DashboardController::class, 'downloadPDF'])->name('dashboard.download');
    Route::get('/autocomplete-city', [DashboardController::class, 'autocompleteCity'])->name('city.autocomplete');
});
