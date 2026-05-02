<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trigger extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'workflow_id',
        'created_by',
        'updated_by',
        'type',
        'config',
        'enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'enabled' => 'boolean',
    ];

    /**
     * Workflow that owns the trigger.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * User who created the trigger.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the trigger.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
