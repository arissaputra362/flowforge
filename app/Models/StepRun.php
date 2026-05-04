<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StepRun extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'workflow_run_id',
        'created_by',
        'updated_by',
        'step_id',
        'attempt',
        'status',
        'input',
        'last_error',
        'output',
        'started_at',
        'finished_at',
        'ai_analysis',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'ai_analysis' => 'array',
        'last_error' => 'string',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Workflow run that owns the step run.
     */
    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }

    /**
     * Logs produced by the step run.
     */
    public function executionLogs(): HasMany
    {
        return $this->hasMany(ExecutionLog::class);
    }

    /**
     * User who created the step run.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the step run.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
