<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('workflows', WorkflowController::class)->only([
        'index',
        'store',
        'show',
        'update',
    ]);

    Route::post('/workflows/{workflow}/run', [WorkflowController::class, 'trigger']);

    Route::apiResource('users', UserController::class)->only([
        'index',
        'store',
        'show',
        'update',
    ]);
});
