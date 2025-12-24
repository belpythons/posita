<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Pos;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Welcome page
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// General authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard redirect
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('pos.session.create');
    })->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Role: admin only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Partners CRUD
    Route::resource('partners', Admin\PartnerController::class);

    // Box Templates CRUD
    Route::resource('box-templates', Admin\BoxTemplateController::class);

    // User Management CRUD
    Route::resource('users', Admin\UserManagementController::class);
});

/*
|--------------------------------------------------------------------------
| POS Routes (Role: employee)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:employee'])->prefix('pos')->name('pos.')->group(function () {
    // Shop Session
    Route::get('/open', [Pos\ShopSessionController::class, 'create'])->name('session.create');
    Route::post('/open', [Pos\ShopSessionController::class, 'store'])->name('session.store');
    Route::get('/close', [Pos\ShopSessionController::class, 'showClose'])->name('session.close');
    Route::post('/close', [Pos\ShopSessionController::class, 'close'])->name('session.close.store');

    // Consignment Management
    Route::get('/consignment', [Pos\ConsignmentController::class, 'index'])->name('consignment.index');
    Route::post('/consignment', [Pos\ConsignmentController::class, 'store'])->name('consignment.store');
    Route::patch('/consignment/{consignment}/sold', [Pos\ConsignmentController::class, 'updateSold'])->name('consignment.sold');
    Route::post('/consignment/bulk-update', [Pos\ConsignmentController::class, 'bulkUpdateSold'])->name('consignment.bulk-update');

    // Box Order (Fitur Amar)
    Route::get('/box', [Pos\BoxOrderController::class, 'index'])->name('box.index');
    Route::get('/box/create/{template}', [Pos\BoxOrderController::class, 'create'])->name('box.create');
    Route::post('/box', [Pos\BoxOrderController::class, 'store'])->name('box.store');
    Route::post('/box/{order}/proof', [Pos\BoxOrderController::class, 'uploadProof'])->name('box.proof');
    Route::patch('/box/{order}/status', [Pos\BoxOrderController::class, 'updateStatus'])->name('box.status');
});

require __DIR__ . '/auth.php';
