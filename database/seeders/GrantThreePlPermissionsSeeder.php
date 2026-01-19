<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class GrantThreePlPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view three-pl',
            'create three-pl',
            'update three-pl',
            'delete three-pl',
            'export three-pl',
            'import three-pl',
        ];

        $role = Role::where('name', 'admin')->first();

        if ($role) {
            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && !$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
            $this->command->info('3PL permissions granted to admin role.');
        } else {
            $this->command->error('Admin role not found.');
        }
    }
}
