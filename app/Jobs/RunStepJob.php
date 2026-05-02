<?php

namespace App\Jobs;

use App\Models\StepRun;
use App\Models\StepRun as StepRunModel;
use App\Services\Execution\StepExecutorFactory;
use App\Services\ExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $stepRunId;
    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 10, 20];
    }

    public function __construct(string $stepRunId)
    {
        $this->stepRunId = $stepRunId;
    }

    public function handle(ExecutionService $executionService)
    {
        // Lock and delegate step execution to ExecutionService
        $lockKey = "step_run_{$this->stepRunId}";

        Cache::lock($lockKey, 30)->get(function () use ($executionService) {
            try {
                $executionService->executeStepRun($this->stepRunId);
            } catch (\RuntimeException $e) {
                // dependency not ready; release for retry
                $this->release(5);
            }
        });
    }
}
