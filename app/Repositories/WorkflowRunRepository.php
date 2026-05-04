<?php

namespace App\Repositories;

use App\Models\WorkflowRun;

class WorkflowRunRepository
{
    public function findWithDetails(string $id): WorkflowRun
    {
        return WorkflowRun::with([
            'workflow',
            'workflow.triggers',
            'workflowVersion',
            'stepRuns' => function ($query) {
                $query->orderBy('created_at');
            },
            'stepRuns.executionLogs' => function ($query) {
                $query->orderBy('created_at');
            },
        ])->findOrFail($id);
    }
}
