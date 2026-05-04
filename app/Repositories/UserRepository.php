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

    public function paginateWithFilters(?string $tenantId, array $filters)
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $role = $filters['role'] ?? null;

        $query = User::query()
            ->with(['tenant', 'roles'])
            ->when($tenantId, fn ($builder) => $builder->where('tenant_id', $tenantId));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhereHas('roles', function ($roleQuery) use ($search) {
                        $roleQuery->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        if (! empty($role)) {
            $query->whereHas('roles', function ($builder) use ($role) {
                $builder->where('name', $role);
            });
        }

        $allowedSorts = ['name', 'email', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage, ['*'], 'page', $page);
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

    public function update(User $user, array $data)
    {
        $user->update($data);

        return $user->fresh(['tenant', 'roles', 'createdBy', 'updatedBy']);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
