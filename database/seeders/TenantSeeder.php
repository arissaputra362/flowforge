<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first();

        Tenant::query()->insert([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
                'name' => 'Tenant Demo',
                'metadata' => json_encode(['tier' => 'demo']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
                'name' => 'Tenant Internal',
                'metadata' => json_encode(['tier' => 'internal']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
