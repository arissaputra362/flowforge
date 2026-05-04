<?php

namespace App\Services;

use App\Models\StepRun;
use App\Repositories\RunMonitorRepository;

class RunMonitorService
{
    public function __construct(private readonly RunMonitorRepository $repository)
    {
    }

    public function poll(string $runId, ?string $tenantId, int $since): array
    {
        $run = $this->repository->findRunForTenant($runId, $tenantId);

        $steps = $this->repository->listStepStates($run->id)
            ->map(function (StepRun $step) {
                $output = $step->output ?? null;
                if (is_array($output) && isset($output['body']) && is_string($output['body']) && strlen($output['body']) > 500) {
                    $output['body'] = substr($output['body'], 0, 500) . '...[truncated]';
                }

                return [
                    'step_id' => $step->step_id,
                    'status' => $step->status,
                    'attempt' => $step->attempt,
                    'output' => $output,
                    'error' => $step->last_error,
                    'ai_analysis' => $step->ai_analysis,
                    'started_at' => optional($step->started_at)->toIso8601String(),
                    'finished_at' => optional($step->finished_at)->toIso8601String(),
                ];
            })
            ->values();

        $logs = $this->repository->listLogsSince($run->id, $since);
        $lastSeq = $logs->last()?->seq ?? $since;

        return [
            'run' => [
                'id' => $run->id,
                'status' => $run->status,
            ],
            'steps' => $steps,
            'logs' => $logs,
            'last_seq' => $lastSeq,
        ];
    }
}
