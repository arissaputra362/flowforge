<?php

namespace App\Repositories;

use App\Models\Tenant;

class TenantRepository
{
    public function firstOrCreate(array $data): Tenant
    {
        return Tenant::firstOrCreate($data);
    }

    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    public function update(array $data, string $id)
    {
        return Tenant::where('id', $id)->update($data);
    }
}
