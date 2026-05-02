<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\WorkflowRun;

Broadcast::channel('workflow.{id}', function ($user, $id) {
    if (! $user) {
        return false;
    }

    $run = WorkflowRun::find($id);

    if (! $run) {
        return false;
    }

    return $user->tenant_id === $run->tenant_id;
});
