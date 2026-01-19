<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ThreePlPermissionSeeder extends Seeder
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

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['resource' => 'three-pl']);
        }
    }
}
