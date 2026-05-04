<?php

namespace App\Jobs;

use App\Models\StepRun;
use App\Services\ExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RunStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $stepRunId;
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes - hard limit for job execution
    // Note: Individual steps may have their own timeout (e.g., HTTP timeout)
    // configured via step definition. The job timeout ensures we don't
    // have zombie jobs running forever.

    public function __construct(string $stepRunId)
    {
        $this->stepRunId = $stepRunId;
    }

    /**
     * Exponential backoff: 2^attempt detik.
     * attempt 1 → 2s, attempt 2 → 4s, attempt 3 → 8s
     */
    public function backoff(): array
    {
        return array_map(
            fn(int $attempt) => pow(2, $attempt),
            range(1, $this->tries)
        );
        // → [2, 4, 8]
    }

    public function handle(ExecutionService $executionService): void
    {
        $lockKey = "step_run_{$this->stepRunId}";

        $acquired = Cache::lock($lockKey, 60)->get(function () use ($executionService) {
            try {
                $executionService->executeStepAttempt(
                    $this->stepRunId,
                    $this->tries  // ✅ satu sumber kebenaran
                );
            } catch (\RuntimeException $e) {
                if (str_contains($e->getMessage(), 'Unfinished dependencies')) {
                    // Dependency belum siap — release untuk retry
                    $this->release(5);
                    return;
                }

                // Exception retryable lain — rethrow supaya queue backoff jalan
                throw $e;
            }
        });

        // Lock tidak bisa didapat → worker lain sedang eksekusi step ini
        if ($acquired === null) {
            $this->release(5);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $stepRun = StepRun::find($this->stepRunId);
        if (!$stepRun) return;

        // Jika sudah failed/success, jangan proses lagi
        if (in_array($stepRun->status, ['failed', 'success', 'skipped'])) {
            // Tetap cek workflow completion kalau belum complete
            app(\App\Services\ExecutionService::class)
                ->checkAndCompleteWorkflow($stepRun->workflowRun);
            return;
        }

        $aiSvc = app(\App\Services\AI\FailureAnalyzerService::class);
        $analysis = $aiSvc->analyze($stepRun);

        $stepRun->update([
            'status'      => 'failed',
            'finished_at' => now(),
            'last_error'  => $exception->getMessage(),
            'ai_analysis' => $analysis,
        ]);

        event(new \App\Events\StepFailed($stepRun, $exception->getMessage()));

        // trigger cek workflow completion
        app(\App\Services\ExecutionService::class)
            ->checkAndCompleteWorkflow($stepRun->workflowRun);
    }
}
