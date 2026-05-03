<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(array $array, string $id)
    {
        return User::where('id', $id)->update($array);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}