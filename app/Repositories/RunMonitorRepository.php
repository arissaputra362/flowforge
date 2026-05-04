<?php

namespace App\Repositories;

use App\Models\ExecutionLog;
use App\Models\StepRun;
use App\Models\WorkflowRun;
use Illuminate\Database\Eloquent\Collection;

class RunMonitorRepository
{
    public function findRunForTenant(string $runId, ?string $tenantId): WorkflowRun
    {
        return WorkflowRun::query()
            ->where('id', $runId)
            ->when($tenantId, fn($query) => $query->where('tenant_id', $tenantId))
            ->firstOrFail();
    }

    /**
     * @return Collection<int, StepRun>
     */
    public function listStepStates(string $runId): Collection
    {
        return StepRun::query()
            ->where('workflow_run_id', $runId)
            ->get([
                'step_id',
                'status',
                'attempt',
                'output',
                'last_error',
                'ai_analysis',
                'started_at',
                'finished_at',
            ]);
    }

    /**
     * @return Collection<int, ExecutionLog>
     */
    public function listLogsSince(string $runId, int $since): Collection
    {
        return ExecutionLog::query()
            ->where('workflow_run_id', $runId)
            ->where('seq', '>', $since)
            ->orderBy('seq')
            ->get([
                'seq',
                'level',
                'message',
                'context',
                'created_at',
            ]);
    }
}
