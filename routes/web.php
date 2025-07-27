<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\FileController;

Route::get('/', function () {return Inertia::render('Welcome');})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('transactions', function () {return Inertia::render('Transactions');})->name('transactions');
    Route::get('advisor', function () {return Inertia::render('AIAdvisor');})->name('advisor');

    Route::get('files/upload', [FileController::class, 'create'])->name('files.create');
    Route::post('files/upload', [FileController::class, 'store'])->name('files.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
