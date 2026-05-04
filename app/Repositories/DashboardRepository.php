<?php

namespace App\Repositories;

use App\Models\ExecutionLog;
use App\Models\StepRun;
use App\Models\Tenant;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function findTenantOverview(?string $tenantId): ?Tenant
    {
        if ($tenantId === null) {
            return null;
        }

        return Tenant::query()
            ->withCount(['users', 'workflows'])
            ->find($tenantId);
    }

    public function buildSummaryMetrics(?string $tenantId): array
    {
        $workflowQuery = $this->workflowQuery($tenantId);
        $runQuery = $this->runQuery($tenantId);
        $recentWindowStart = now()->subDay();

        $recentRunMetrics = (clone $runQuery)
            ->where('created_at', '>=', $recentWindowStart)
            ->get(['id', 'status', 'created_at', 'updated_at']);

        $finishedRecentRuns = $recentRunMetrics->whereIn('status', ['completed', 'failed']);
        $completedRecentRuns = $recentRunMetrics->where('status', 'completed')->count();
        $failedRecentRuns = $recentRunMetrics->where('status', 'failed')->count();
        $finishedRecentCount = $completedRecentRuns + $failedRecentRuns;

        $avgDurationSeconds = $finishedRecentRuns->isNotEmpty()
            ? (int) round($finishedRecentRuns->avg(function ($run) {
                return $run->updated_at?->diffInSeconds($run->created_at) ?? 0;
            }))
            : 0;

        return [
            'workflows_total' => (clone $workflowQuery)->count(),
            'runs_total' => (clone $runQuery)->count(),
            'active_runs' => (clone $runQuery)->whereIn('status', ['running', 'pending'])->count(),
            'completed_runs' => (clone $runQuery)->where('status', 'completed')->count(),
            'failed_runs' => (clone $runQuery)->where('status', 'failed')->count(),
            'recent_runs' => $recentRunMetrics->count(),
            'success_rate' => $finishedRecentCount > 0 ? round(($completedRecentRuns / $finishedRecentCount) * 100) : 0,
            'failure_rate' => $finishedRecentCount > 0 ? round(($failedRecentRuns / $finishedRecentCount) * 100) : 0,
            'avg_duration_seconds' => $avgDurationSeconds,
            'avg_duration_label' => $this->formatDuration($avgDurationSeconds),
        ];
    }

    public function recentWorkflows(?string $tenantId, int $limit = 4): Collection
    {
        return (clone $this->workflowQuery($tenantId))
            ->withCount('runs')
            ->with(['latestVersion', 'triggers'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function recentRuns(?string $tenantId, int $limit = 6): Collection
    {
        return (clone $this->runQuery($tenantId))
            ->with([
                'workflow:id,name',
                'workflowVersion:id,workflow_id,version',
            ])
            ->withCount([
                'stepRuns as total_steps',
                'stepRuns as finished_steps' => function ($query) {
                    $query->whereIn('status', ['success', 'failed', 'skipped']);
                },
            ])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function recentLogs(?string $tenantId, int $limit = 8): Collection
    {
        return ExecutionLog::query()
            ->whereHas('workflowRun', function ($query) use ($tenantId) {
                if ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                }
            })
            ->with([
                'workflowRun.workflow:id,name',
                'stepRun:id,step_id,workflow_run_id',
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function failureHotspots(?string $tenantId, int $limit = 5): Collection
    {
        return StepRun::query()
            ->selectRaw('step_id, COUNT(*) as failures')
            ->whereHas('workflowRun', function ($query) use ($tenantId) {
                if ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                }
            })
            ->where('status', 'failed')
            ->groupBy('step_id')
            ->orderByDesc('failures')
            ->limit($limit)
            ->get();
    }

    private function workflowQuery(?string $tenantId)
    {
        return Workflow::query()->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId));
    }

    private function runQuery(?string $tenantId)
    {
        return WorkflowRun::query()->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId));
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return sprintf('%dm %02ds', $minutes, $remainingSeconds);
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%dh %02dm', $hours, $remainingMinutes);
    }
}