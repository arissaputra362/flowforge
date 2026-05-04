<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;

class WorkflowPolicy
{
    /**
     * Determine if the user can view any workflows
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the workflow
     */
    public function view(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    /**
     * Determine if the user can create workflows
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the workflow
     */
    public function update(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    /**
     * Determine if the user can delete the workflow
     */
    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    /**
     * Determine if the user can trigger the workflow
     */
    public function trigger(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    /**
     * Determine if the user can restore the workflow
     */
    public function restore(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    /**
     * Determine if the user can permanently delete the workflow
     */
    public function forceDelete(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }
}
