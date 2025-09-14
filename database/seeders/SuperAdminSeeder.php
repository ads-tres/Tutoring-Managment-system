<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached permission data to avoid issues
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure the 'super-admin' role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);

        // 3. Remove all other users to start clean
        User::query()->where('email', '!=', 'superadmin@admin.com')->delete();

        // 4. Create or fetch the super-admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@admin.com'], // unique identifier
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // simple default pw for now
            ]
        );

        // 5. Assign the 'super-admin' role if not already assigned
        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }

        // 6. Reset permissions cache for final cleanup
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
