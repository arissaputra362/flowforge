<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\WorkflowRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    private UserRepository $userRepository;
    private WorkflowRepository $workflowRepository;

    public function __construct(UserRepository $userRepository, WorkflowRepository $workflowRepository)
    {
        $this->userRepository = $userRepository;
        $this->workflowRepository = $workflowRepository;
    }

    public function baseQuery(?string $tenantId)
    {
        return $this->userRepository->query($tenantId);
    }

    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            // Create user
            $user = $this->userRepository->create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'tenant_id'  => $data['tenant_id'],
                'created_by' => $data['created_by'],
            ]);

            // Assign role
            $user->assignRole($data['role']);

            DB::commit();

            return [
                'is_success' => true,
                'data' => $user
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return [
                'is_success' => false,
                'data' => [
                    'message' => 'Failed to create user'
                ]
            ];
        }
    }

    public function findForTenant(string $id, ?string $tenantId = null): User
    {
        return $this->userRepository->findForTenant($id, $tenantId);
    }

    public function update(User $user, array $data)
    {
        DB::beginTransaction();

        try {
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'updated_by' => $data['updated_by'],
            ];

            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $updatedUser = $this->userRepository->update($user, $payload);
            $updatedUser->syncRoles([$data['role']]);

            DB::commit();

            return [
                'is_success' => true,
                'data' => $updatedUser->fresh(['tenant', 'roles', 'createdBy', 'updatedBy']),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return [
                'is_success' => false,
                'data' => [
                    'message' => 'Failed to update user',
                ],
            ];
        }
    }

}
