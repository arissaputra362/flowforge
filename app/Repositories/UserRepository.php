<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository{
    public function query(?string $tenantId = null){
        $query = User::query()->with('tenant')->with('roles');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findForTenant(string $id, ?string $tenantId = null): User
    {
        $query = User::query()
            ->with(['tenant', 'roles', 'createdBy', 'updatedBy']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->findOrFail($id);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh(['tenant', 'roles', 'createdBy', 'updatedBy']);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
