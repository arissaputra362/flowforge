<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasUuid;

    public static $snakeAttributes = false;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'updated_by',
        'name',
        'description',
    ];

    /**
     * Tenant that owns the workflow.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Workflow versions for the workflow.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class);
    }

    /**
     * Latest immutable workflow version.
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(WorkflowVersion::class)
            ->orderByDesc('created_at')
            ->orderByRaw('CAST(version AS INTEGER) DESC');
    }

    /**
     * Runs started from the workflow.
     */
    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    /**
     * Triggers attached to the workflow.
     */
    public function triggers(): HasMany
    {
        return $this->hasMany(Trigger::class);
    }

    /**
     * User who created the workflow.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the workflow.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
