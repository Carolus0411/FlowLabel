<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        settings([
            'opening_balance_period' => '2023',
            'active_period' => '2024',

            'account_receivable_code' => '104-001',
            'account_payable_code' => '201-001',
            'vat_out_code' => '203-008',
            'stamp_code' => '631-002',
            'cash_account_code' => '101',
            'bank_account_code' => '102',
            'ar_prepaid_code' => '204-002',

            'cash_in_code' => 'BKM-',
            'cash_out_code' => 'BKK-',
            'bank_in_code' => 'BBM-',
            'bank_out_code' => 'BBK-',
        ]);
    }
}
