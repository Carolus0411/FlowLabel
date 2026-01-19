<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MissingPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Request permissions
            'view request',
            'create request',
            'update request',
            'delete request',

            // Sales Order permissions
            'view sales-order',
            'create sales-order',
            'update sales-order',
            'delete sales-order',
            'export sales-order',
            'import sales-order',

            // Recipe permissions
            'view recipe',
            'create recipe',
            'update recipe',
            'delete recipe',

            // BOM permissions
            'view bom',
            'create bom',
            'update bom',
            'delete bom',

            // Stock Report permissions
            'view stock-report',

            // Stock Movement permissions
            'view stock-movement',

            // Item Type permissions
            'view item-type',
            'create item-type',
            'update item-type',
            'delete item-type',
        ];

        foreach ($permissions as $name) {
            $resource = explode(' ', $name)[1]; // Get resource name from permission
            Permission::firstOrCreate(['name' => $name], ['resource' => $resource]);
        }
    }
}
