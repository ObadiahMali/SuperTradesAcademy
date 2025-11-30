<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = 'fahad@gmail.com';
        $password = 'fahad123';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
            ]
        );

        $role = Role::firstOrCreate(['name' => 'adminstrator']);
        $user->assignRole($role);
    }
}