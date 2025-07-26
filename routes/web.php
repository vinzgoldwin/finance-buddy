<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StatementController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('transactions', function () {
        return Inertia::render('Transactions');
    })->name('transactions');

    Route::get('statements/upload', [StatementController::class, 'create'])->name('statements.create');
    Route::post('statements/upload', [StatementController::class, 'store'])->name('statements.store');

    Route::get('advisor', function () {
        return Inertia::render('AIAdvisor');
    })->name('advisor');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
