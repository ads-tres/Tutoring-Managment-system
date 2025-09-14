<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // STEP 1: Clear cached permissions to avoid stale data issues
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // STEP 2: Create main roles if they don't already exist
        $roleNames = ['manager', 'subordinate', 'tutor', 'parent', 'accountant'];
        foreach ($roleNames as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // STEP 3: Create a manager user explicitly
        $manager = User::firstOrCreate(
            ['email' => 'manager@demo.com'], // unique identifier to avoid duplicates
            [
                'name'     => 'Manager User',
                'password' => Hash::make('password'), // simple demo password
            ]
        );
        // Assign 'manager' role — grants Filament access and full dashboard privileges
        if (!$manager->hasRole('manager')) {
            $manager->assignRole('manager');
        }

        // STEP 4: Use factories to create other users per role
        User::factory(3)->create()->each(function (User $user) {
            if (!$user->hasRole('subordinate')) {
                $user->assignRole('subordinate');
            }
        });

        User::factory(5)->create()->each(function (User $user) {
            if (!$user->hasRole('tutor')) {
                $user->assignRole('tutor');
            }
        });

        User::factory(5)->create()->each(function (User $user) {
            if (!$user->hasRole('parent')) {
                $user->assignRole('parent');
            }
        });

        User::factory(2)->create()->each(function (User $user) {
            if (!$user->hasRole('accountant')) {
                $user->assignRole('accountant');
            }
        });

        // STEP 5: After seeding, clear cache again (best practice)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
