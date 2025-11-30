<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Define permissions
        $permissions = [
            'manage employees',
            'view reports',
            'manage students',
            'manage payments',
            'manage expenses',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Create roles with correct names
        $administrator = Role::firstOrCreate(['name' => 'administrator']);
        $secretary = Role::firstOrCreate(['name' => 'secretary']);

        // Assign permissions to roles
        $administrator->givePermissionTo(Permission::all());
        $secretary->givePermissionTo(['manage students','manage payments','manage expenses']);

        // Create test users and assign roles
        $adminUser = User::firstOrCreate(
            ['email' => 'fahad@gmail.com'],
            [
                'name' => 'fahad',
                'password' => Hash::make('fahad123'),
            ]
        );
        $adminUser->assignRole($administrator);

        $secretaryUser = User::firstOrCreate(
            ['email' => 'tracy@gmail.com'],
            [
                'name' => 'tracy',
                'password' => Hash::make('tracy123'),
            ]
        );
        $secretaryUser->assignRole($secretary);
    }
}