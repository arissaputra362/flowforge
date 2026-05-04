<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DemoController;
use App\Http\Controllers\Web\WebDashboardController;
use App\Http\Controllers\Web\WebWorkflowController;
use App\Http\Controllers\Web\WebRunController;
use App\Http\Controllers\Web\WebUserController;

Route::get('/', function () {
    return redirect('/workflows');
});

Route::get('/register', [AuthController::class, 'registerIndex'])->name('register');
Route::post('/register', [AuthController::class, 'registerStore'])->name('register.store');

Route::get('/login', [AuthController::class, 'loginIndex'])->name('login');
Route::post('/login', [AuthController::class, 'loginStore'])->name('login.store');

Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [WebDashboardController::class, 'index'])
        ->name('dashboard');

    // Workflows
    Route::prefix('workflows')
        ->name('webworkflows.')
        ->group(function () {

        // Viewer only
        Route::get('/', [WebWorkflowController::class, 'index'])->name('index');
        
        // Admin and Editor
        Route::middleware('role:admin,editor')->group(function () {
            Route::get('/create', [WebWorkflowController::class, 'create'])->name('create');
            Route::get('/{workflow}/edit', [WebWorkflowController::class, 'edit'])->name('edit');
        });

        Route::get('/{workflow}', [WebWorkflowController::class, 'show'])->name('show');
        Route::get('/{workflow}/run', [WebWorkflowController::class, 'run'])->name('run');
    });

    // Users (Admin only)
    Route::middleware('role:admin')
        ->prefix('users')
        ->name('webusers.')
        ->group(function () {

            Route::get('/', [WebUserController::class, 'index'])->name('index');
            Route::post('/', [WebUserController::class, 'store'])->name('store');
            Route::get('/create', [WebUserController::class, 'create'])->name('create');
            Route::get('/{user}', [WebUserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [WebUserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [WebUserController::class, 'update'])->name('update');
        });
});

// Run Monitor
Route::get('/runs/{id}', [WebRunController::class, 'show']);

// Demo
Route::get('/realtime-demo', [DemoController::class, 'index']);
Route::get('/test-realtime-trigger', [DemoController::class, 'trigger']);
