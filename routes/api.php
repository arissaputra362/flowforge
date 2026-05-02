<?php

use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::apiResource('workflows', WorkflowController::class)->only([
    'index',
    'store',
    'show',
    'update',
]);
