<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionLog extends Model
{
    protected $primaryKey = 'seq';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'workflow_run_id',
        'step_run_id',
        'level',
        'message',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Step run that generated the log entry.
     */
    public function stepRun(): BelongsTo
    {
        return $this->belongsTo(StepRun::class);
    }

    /**
     * Workflow run that owns the log entry.
     */
    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }
}
