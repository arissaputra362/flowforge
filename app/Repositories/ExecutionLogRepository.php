<?php

namespace App\Repositories;

use App\Models\ExecutionLog;

class ExecutionLogRepository
{
    /**
     * Create a new execution log entry.
     */
    public function create(array $data): ExecutionLog
    {
        return ExecutionLog::create($data);
    }

    /**
     * Get the first execution log entry.
     */
    public function first(): ?ExecutionLog
    {
        return ExecutionLog::first();
    }

    /**
     * Get the first execution log entry by level.
     */
    public function firstByLevel(string $level): ?ExecutionLog
    {
        return ExecutionLog::where('level', $level)->first();
    }
}
