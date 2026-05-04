<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\RunMonitorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('mock')->group(function () {

    // ================= PAYMENT =================
    Route::post('/payment/charge', function (Request $request) {

        // simulate delay (ms)
        usleep(($request->input('delay', 0)) * 1000);

        // simulate failure
        if ($request->input('fail') == true) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Payment gateway error'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'transaction_id' => 'trx_' . uniqid(),
            'amount' => $request->input('amount', 1000)
        ]);
    });


    // ================= INVENTORY =================
    Route::get('/inventory/check', function (Request $request) {

        usleep(($request->input('delay', 0)) * 1000);

        // force stock value
        if ($request->has('stock')) {
            $stock = (int) $request->input('stock');
        } else {
            $stock = rand(0, 10);
        }

        // simulate failure
        if ($request->input('fail') == true) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory service down'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'product_id' => $request->input('product_id', 1),
            'stock' => $stock,
            'available' => $stock > 0
        ]);
    });


    // ================= EMAIL =================
    Route::post('/email/send', function (Request $request) {

        usleep(($request->input('delay', 0)) * 1000);

        if ($request->input('fail') == true) {
            return response()->json([
                'status' => 'failed',
                'message' => 'SMTP error'
            ], 500);
        }

        return response()->json([
            'status' => 'sent',
            'to' => $request->input('to', 'user@email.com'),
            'subject' => $request->input('subject', 'Notification'),
            'message_id' => 'msg_' . uniqid()
        ]);
    });

});

Route::post('/webhooks/{token}', WebhookController::class)
    ->middleware('throttle:30,1')
    ->name('webhooks.trigger');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
    Route::get('/workflows/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show');
    Route::post('/workflows', [WorkflowController::class, 'store'])->name('workflows.store')
        ->middleware('role:admin,editor');
    Route::put('/workflows/{workflow}', [WorkflowController::class, 'update'])->name('workflows.update')
        ->middleware('role:admin,editor');

    Route::post('/workflows/{workflow}/run', [WorkflowController::class, 'trigger'])->name('workflows.trigger');

    Route::get('/runs/{run}/poll', [RunMonitorController::class, 'poll']);

    Route::apiResource('users', UserController::class)->only([
        'index',
        'store',
        'show',
        'update',
    ])->middleware('role:admin');
});
