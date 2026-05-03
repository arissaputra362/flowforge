<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorkflowRunService;
use Illuminate\View\View;

class WebRunController extends Controller
{
    private WorkflowRunService $workflowRunService;

    public function __construct(WorkflowRunService $workflowRunService)
    {
        $this->workflowRunService = $workflowRunService;
    }

    public function show(string $id): View
    {
        $run = $this->workflowRunService->findWithDetails($id);

        return view('runs.show', [
            'run' => $run,
            'stepRuns' => $run->stepRuns,
        ]);
    }
}
