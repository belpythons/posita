<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PosController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // POS Routes
    Route::get('/pos', [PosController::class, 'index'])->name('pos.dashboard');
    Route::get('/pos/open', [PosController::class, 'createOpen'])->name('pos.open');
    Route::post('/pos/open', [PosController::class, 'storeOpen'])->name('pos.store-open');
    Route::get('/pos/close', [PosController::class, 'createClose'])->name('pos.close');
    Route::put('/pos/close/{shopSession}', [PosController::class, 'updateClose'])->name('pos.update-close');
});

require __DIR__ . '/auth.php';
