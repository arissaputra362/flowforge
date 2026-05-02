<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    dd("aa");
    return view('welcome');
});

Route::get('/realtime-demo', function () {
    $runId = request()->query('run_id', 'demo-run-id');

    return view('realtime-demo', [
        'runId' => $runId,
    ]);
});
