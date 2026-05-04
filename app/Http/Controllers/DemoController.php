<?php

namespace App\Http\Controllers;

use App\Services\DemoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DemoController extends Controller
{
    private DemoService $demoService;

    public function __construct(DemoService $demoService)
    {
        $this->demoService = $demoService;
    }

    public function index(): View
    {
        $runId = request()->query('run_id', 'demo-run-id');

        return view('realtime-demo', [
            'runId' => $runId,
        ]);
    }

    public function trigger(): RedirectResponse
    {
        $run = $this->demoService->triggerRealtimeDemo();

        return redirect('/realtime-demo?run_id=' . $run->id);
    }
}
