<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin role
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['guard_name' => 'web']
        );

        // Get all existing permissions
        $allPermissions = Permission::all();

        // If no permissions exist, create basic ones
        if ($allPermissions->isEmpty()) {
            $permissions = [
                // User Management
                'view users',
                'create users',
                'update users',
                'delete users',
                
                // Role Management
                'view roles',
                'create roles',
                'update roles',
                'delete roles',
                'assign roles',
                
                // Permission Management
                'view permissions',
                'create permissions',
                'update permissions',
                'delete permissions',
                
                // Order Label
                'view order-label',
                'create order-label',
                'update order-label',
                'delete order-label',
                'import order-label',
                'export order-label',
                'print order-label',
                
                // 3PL Management
                'view three-pl',
                'create three-pl',
                'update three-pl',
                'delete three-pl',
                
                // Settings
                'view general-setting',
                'update general-setting',
                
                // User Logs
                'view user logs',
                
                // System
                'access all features',
                'manage system',
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission],
                    ['guard_name' => 'web']
                );
            }

            $allPermissions = Permission::all();
        }

        // Assign all permissions to Super Admin role
        $superAdminRole->syncPermissions($allPermissions);

        // Create or update Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@labsysflow.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        // Assign Super Admin role to user
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('✓ Super Admin user created successfully!');
        $this->command->info('  Email: superadmin@labsysflow.com');
        $this->command->info('  Password: password');
        $this->command->info('  Role: Super Admin');
        $this->command->info('  Permissions: ' . $allPermissions->count() . ' permissions assigned');
    }
}
