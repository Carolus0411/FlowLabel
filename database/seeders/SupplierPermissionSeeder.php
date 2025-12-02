<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SupplierPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view supplier',
            'create supplier',
            'update supplier',
            'delete supplier',
            'export supplier',
            'import supplier',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['resource' => 'supplier']);
        }
    }
}
