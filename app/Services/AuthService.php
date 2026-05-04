<?php

namespace App\Services;

use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService{
    private TenantRepository $tenantRepository;
    private UserRepository $userRepository;

    public function __construct(TenantRepository $tenantRepository, UserRepository $userRepository)
    {
        $this->tenantRepository = $tenantRepository;
        $this->userRepository = $userRepository;
    }

    public function register(array $data)
    {
       DB::beginTransaction();
        try{
            // 1. Create Tenant
            $tenant = $this->tenantRepository->create([
                'name' => $data['tenant_name']
            ]);

            // 2. Create User
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tenant_id' => $tenant->id,
            ]);

            // 3. Assign Role (ADMIN)
            $user->assignRole('admin');

            // 4. Generate Token
            $token = $user->createToken('api')->plainTextToken;

            // 5. Update create by tenant and user
            $this->tenantRepository->update([
                'created_by' => $user->id
            ], $tenant->id);

            $this->userRepository->update($user,[
                'created_by' => $user->id
            ]);

            DB::commit();
            
            return [
                'is_success' => true,
                'data' => [
                    'message' => 'Register success',
                    'token'   => $token,
                    'user'    => $user,
                    'tenant'  => $tenant
                ]
            ];
        }catch(Exception $exception){
            Log::error($exception);
            DB::rollBack();
            return [
                'is_success' => false,
                'data' => [
                    'message' => 'Register failed. Something went wrong.',
                    'error' => $exception->getMessage()
                ]
            ];
        }
    }

    public function login(array $data){
        $user = $this->userRepository->findByEmail($data['email']);
        if(!$user || !Hash::check($data['password'], $user->password)){
            return [
                'is_success' => false,
                'data' => [
                    'message' => 'Credentials not valid',
                ]
            ];
        }

        $token = $user->createToken('api')->plainTextToken;
        $user->token = $token;

        return [
            'is_success' => true,
            'data' => [
                'message' => 'Login success',
                'token'   => $token,
                'user'    => $user,
                'tenant'  => $user->tenant
            ]
        ];
    }
    
}