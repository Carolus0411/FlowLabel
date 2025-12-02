<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class BankOutPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view bank-out',
            'create bank-out',
            'update bank-out',
            'delete bank-out',
            'close bank-out',
            'void bank-out',
            'export bank-out',
            'import bank-out',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['resource' => 'bank-out']);
        }
    }
}
