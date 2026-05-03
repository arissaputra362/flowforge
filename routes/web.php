<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DemoController;
use App\Http\Controllers\Web\WebDashboardController;
use App\Http\Controllers\Web\WebWorkflowController;
use App\Http\Controllers\Web\WebRunController;

Route::get('/', function () {
    return redirect('/workflows');
});

Route::get('/register', [AuthController::class, 'registerIndex'])->name('register');
Route::post('/register', [AuthController::class, 'registerStore'])->name('register.store');

Route::get('/login', [AuthController::class, 'loginIndex'])->name('login');
Route::post('/login', [AuthController::class, 'loginStore'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [WebDashboardController::class, 'index'])->name('dashboard');

    // Workflow UI
    Route::get('/workflows', [WebWorkflowController::class, 'index']);
    Route::get('/workflows/create', [WebWorkflowController::class, 'create']);
    Route::get('/workflows/{id}', [WebWorkflowController::class, 'show']);
    Route::get('/workflows/{id}/run', [WebWorkflowController::class, 'run']);
});

// Run Monitor
Route::get('/runs/{id}', [WebRunController::class, 'show']);

// Demo
Route::get('/realtime-demo', [DemoController::class, 'index']);
Route::get('/test-realtime-trigger', [DemoController::class, 'trigger']);
