<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SpendingLimitController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\FileController;

Route::get('/', function () {return Inertia::render('Welcome');})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    
    Route::get('advisor', function () {return Inertia::render('AIAdvisor');})->name('advisor');

    Route::get('files/upload', [FileController::class, 'create'])->name('files.create');
    Route::post('files/upload', [FileController::class, 'store'])->name('files.store');
    
    // Spending limit routes
    Route::get('spending-limit', [SpendingLimitController::class, 'show'])->name('spending-limit.show');
    Route::post('spending-limit', [SpendingLimitController::class, 'store'])->name('spending-limit.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
