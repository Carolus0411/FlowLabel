<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PurchasePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view purchase-invoice', 'create purchase-invoice', 'update purchase-invoice', 'delete purchase-invoice', 'export purchase-invoice', 'import purchase-invoice',
            'view purchase-settlement', 'create purchase-settlement', 'update purchase-settlement', 'delete purchase-settlement', 'export purchase-settlement', 'import purchase-settlement',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['resource' => 'purchase-invoice']);
        }
    }
}
