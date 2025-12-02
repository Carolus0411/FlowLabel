<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::firstOrCreate([
            'code' => 'S001',
        ], [
            'code' => 'S001',
            'name' => 'Supplier One',
            'contact_name' => 'John Doe',
            'address_1' => 'Jl. Example No. 123',
            'address_2' => 'Kel. Sample',
            'telephone' => '021-555-1234',
            'mobile_phone' => '081234567890',
            'email' => 'supplier@example.com',
            'npwp' => '12.345.678.9-012.345',
            'information' => 'Demo supplier',
            'term_of_payment' => 30,
            'is_active' => 1,
        ]);
    }
}
