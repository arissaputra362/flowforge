<?php

namespace App\Events;

use App\Models\WorkflowRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WorkflowRun $workflowRun;

    public string $workflowRunId;

    public function __construct(WorkflowRun $workflowRun)
    {
        $this->workflowRun = $workflowRun;
        $this->workflowRunId = (string) $workflowRun->id;
    }

    public function broadcastOn()
    {
        return new Channel('workflow.' . $this->workflowRunId);
    }

    public function broadcastWith(): array
    {
        return [
            'workflow_run_id' => $this->workflowRunId,
            'status' => $this->workflowRun->status,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
