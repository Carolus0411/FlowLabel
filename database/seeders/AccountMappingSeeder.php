<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        settings([
            'account_receivable_code' => '104-001',
            'account_payable_code' => '201-001',
            'vat_out_code' => '203-008',
            'stamp_code' => '631-002',
        ]);
    }
}
