<?php

use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('workflows', WorkflowController::class)->only([
        'index',
        'store',
        'show',
        'update',
    ]);

    Route::post('/workflows/{workflow}/run', [WorkflowController::class, 'trigger']);
});