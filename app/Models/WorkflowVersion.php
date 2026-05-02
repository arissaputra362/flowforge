<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowVersion extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'workflow_id',
        'version',
        'definition',
        'notes',
    ];

    protected $hidden = [
        'dag',
    ];

    protected $appends = [
        'definition',
    ];

    protected $casts = [
        'dag' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Workflow that owns the version.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Alias for the workflow definition JSON payload.
     */
    public function getDefinitionAttribute(): ?array
    {
        return $this->dag;
    }

    /**
     * Alias setter for the workflow definition JSON payload.
     */
    public function setDefinitionAttribute($value): void
    {
        $this->attributes['dag'] = is_string($value) ? $value : json_encode($value);
    }
}
