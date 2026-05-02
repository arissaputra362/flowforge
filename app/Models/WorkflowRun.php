<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'workflow_id',
        'workflow_version_id',
        'tenant_id',
        'created_by',
        'updated_by',
        'started_by',
        'status',
        'input',
        'output',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    /**
     * Workflow that produced the run.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Version used for the run.
     */
    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class);
    }

    /**
     * Tenant that owns the run.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Step runs spawned by the workflow run.
     */
    public function stepRuns(): HasMany
    {
        return $this->hasMany(StepRun::class);
    }

    /**
     * User who created the run.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the run.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * User who started the run.
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }
}
