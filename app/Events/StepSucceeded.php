<?php

namespace App\Events;

use App\Models\StepRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StepSucceeded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StepRun $stepRun;
    public string $workflowRunId;

    public function __construct(StepRun $stepRun)
    {
        $this->stepRun = $stepRun;
        $this->workflowRunId = (string) $stepRun->workflow_run_id;
    }

    public function broadcastOn()
    {
        return new Channel('workflow.' . $this->workflowRunId);
    }

    public function broadcastWith(): array
    {
        $output = $this->stepRun->output;
    
        // Trim body jika terlalu besar
        if (isset($output['body']) && strlen($output['body']) > 500) {
            $output['body'] = substr($output['body'], 0, 500) . '...[truncated]';
        }

        return [
            'step_id'    => $this->stepRun->step_id,
            'status'     => $this->stepRun->status,
            'output'     => $output,
            'attempt'    => $this->stepRun->attempt,
            'timestamp'  => now()->toIso8601String(),
        ];
    }
}
