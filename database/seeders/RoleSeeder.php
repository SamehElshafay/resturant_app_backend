<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage branches',
            'manage categories',
            'manage products',
            'manage users',
            'manage accounting',
            'create vouchers',
            'manage inventory',
            'view reports',
            'pos access',
            'manage tables',
            'manage bookings',
            'manage suppliers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions(['pos access', 'manage tables', 'manage bookings']);

        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->syncPermissions(['manage accounting', 'create vouchers', 'view reports']);

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions(['manage categories', 'manage products', 'manage inventory', 'view reports', 'manage tables']);

        $driver = Role::firstOrCreate(['name' => 'driver']);
    }
}
