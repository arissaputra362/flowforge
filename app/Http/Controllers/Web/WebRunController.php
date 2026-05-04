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

        $stepOrder = collect(data_get($run->workflowVersion, 'definition.steps', []))
            ->pluck('id')
            ->values()
            ->flip();

        $stepRuns = $run->stepRuns
            ->sortBy(function ($stepRun) use ($stepOrder) {
                $position = $stepOrder->get($stepRun->step_id, PHP_INT_MAX);
                $timestamp = optional($stepRun->started_at ?? $stepRun->created_at)->timestamp ?? 0;

                return sprintf('%012d-%012d', $position, $timestamp);
            })
            ->values();

        return view('runs.show', [
            'run' => $run,
            'stepRuns' => $stepRuns,
            'workflowSteps' => collect(data_get($run->workflowVersion, 'definition.steps', [])),
        ]);
    }
}
