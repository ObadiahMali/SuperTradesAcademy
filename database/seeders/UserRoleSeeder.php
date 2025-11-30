<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'administrator']);
        $secretaryRole = Role::firstOrCreate(['name' => 'secretary']);

        // Create Administrator user
        $admin = User::firstOrCreate(
            ['email' => 'fahad@gmail.com'],
            [
                'name' => 'fahad',
                'password' => Hash::make('fahad123'),
            ]
        );
        $admin->assignRole($adminRole);

        // Create Secretary user
        $secretary = User::firstOrCreate(
            ['email' => 'tracy@gmail.com'],
            [
                'name' => 'tracy',
                'password' => Hash::make('tracy123'),
            ]
        );
        $secretary->assignRole($secretaryRole);
    }
}