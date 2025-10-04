<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission; // We need to import Spatie Permission Model

class UserAndRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define and Create Roles
        $superAdminRole  = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $managerRole     = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $accountantRole  = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $tutorRole       = Role::firstOrCreate(['name' => 'tutor', 'guard_name' => 'web']);
        $subordinateRole = Role::firstOrCreate(['name' => 'subordinate', 'guard_name' => 'web']);
        $parentRole      = Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);


        // 2. Define Permissions for Access Control
        // These are the custom permissions you'll use in your Filament components to hide tabs/resources.
        $sharedAccessPermission    = Permission::firstOrCreate(['name' => 'view_shared_tabs', 'guard_name' => 'web']);
        $restrictedAccessPermission = Permission::firstOrCreate(['name' => 'view_restricted_tabs', 'guard_name' => 'web']);

        // 3. Assign Permissions to Roles based on access requirements
        
        // Roles with HIGH/RESTRICTED Access
        $superAdminRole->givePermissionTo(Permission::all()); // Super Admin gets everything
        $managerRole->givePermissionTo([$sharedAccessPermission, $restrictedAccessPermission]);
        $accountantRole->givePermissionTo([$sharedAccessPermission, $restrictedAccessPermission]);

        // Roles with LIMITED/SHARED Access
        $tutorRole->givePermissionTo($sharedAccessPermission);
        $subordinateRole->givePermissionTo($sharedAccessPermission);
        $parentRole->givePermissionTo($sharedAccessPermission);


        // 4. Create Users and Assign Roles (re-using previous user data)

        // Super Admin
        User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Super Admin', 'last_name' => 'Root', 'password' => Hash::make('password'),
            'email_verified_at' => now(), 'salary_per_hour' => 50.00, 'monthly_target_hours' => 160,
        ])->assignRole($superAdminRole);

        // Manager
        User::firstOrCreate(['email' => 'manager@example.com'], [
            'name' => 'Sarah', 'last_name' => 'Connor', 'password' => Hash::make('password'),
            'email_verified_at' => now(), 'salary_per_hour' => 60.00, 'monthly_target_hours' => 140,
        ])->assignRole($managerRole);
        // dd($managerRole->permissions);

        // Accountant
        User::firstOrCreate(['email' => 'accountant@example.com'], [
            'name' => 'Maya', 'last_name' => 'Perez', 'password' => Hash::make('password'),
            'email_verified_at' => now(), 'salary_per_hour' => 35.00, 'monthly_target_hours' => 150,
        ])->assignRole($accountantRole);

        // Tutor (Limited Access)
        User::firstOrCreate(['email' => 'tutor@example.com'], [
            'name' => 'John', 'last_name' => 'Doe', 'password' => Hash::make('password'),
            'email_verified_at' => now(), 'salary_per_hour' => 40.00, 'monthly_target_hours' => 180,
        ])->assignRole($tutorRole);

        // Subordinate (Limited Access)
        User::firstOrCreate(['email' => 'subordinate@example.com'], [
            'name' => 'Tom', 'last_name' => 'Hanks', 'password' => Hash::make('password'),
            'email_verified_at' => now(), 'salary_per_hour' => 30.00, 'monthly_target_hours' => 170,
        ])->assignRole($subordinateRole);

        // Parent (External, Limited Access)
        User::firstOrCreate(['email' => 'parent@example.com'], [
            'name' => 'Emily', 'last_name' => 'Smith', 'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ])->assignRole($parentRole);
    }
}
