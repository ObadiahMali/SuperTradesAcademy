<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CreateAdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = 'stapipsquad@gmail.com';
        $password = 'fahad123';
        $roleName = 'administrator';

        // Create or fetch the user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
            ]
        );

        // If Spatie's Role model is available, ensure the role exists
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);
        }

        // If the User model supports assignRole (HasRoles trait), use it
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($roleName);
            return;
        }

        // Fallback: if users table has a 'role' column, set it
        if (Schema::hasColumn($user->getTable(), 'role')) {
            $user->role = $roleName;
            $user->save();
            return;
        }

        // Final fallback: try to attach via a pivot/relationship named 'roles' if it exists
        if (method_exists($user, 'roles')) {
            try {
                $rolesRelation = $user->roles();
                // If Role model exists, attach the role model; otherwise attach by name if pivot accepts it
                if (class_exists(\Spatie\Permission\Models\Role::class)) {
                    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);
                    $rolesRelation->syncWithoutDetaching([$role->id]);
                } else {
                    // Attempt to insert a simple pivot record (best-effort)
                    $rolesRelation->attach($roleName);
                }
            } catch (\Throwable $e) {
                // ignore and continue; seeder should not crash here
            }
            return;
        }

        // If none of the above applied, leave the user created but unassigned.
        // You can manually assign a role later or install/configure a roles package.
    }
}