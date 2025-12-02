<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class BankInPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view bank-in',
            'create bank-in',
            'update bank-in',
            'delete bank-in',
            'close bank-in',
            'void bank-in',
            'export bank-in',
            'import bank-in',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['resource' => 'bank-in']);
        }
    }
}
