<?php

namespace App\Jobs;

use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Workflow\DagParser;
use App\Services\ExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $workflowId;
    public array $input;

    public function __construct(string $workflowId, array $input = [])
    {
        $this->workflowId = $workflowId;
        $this->input = $input;
    }

    public function handle(ExecutionService $executionService)
    {
        // Delegate all loading/creation/dispatching to the ExecutionService
        $run = $executionService->startWorkflow($this->workflowId, $this->input);

        // broadcast workflow started
        event(new \App\Events\WorkflowStarted($run));
    }
}
