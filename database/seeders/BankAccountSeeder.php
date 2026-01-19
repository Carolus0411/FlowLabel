<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('bank_account')->truncate();
        
        DB::table('bank_account')->insert([
            [
  'id' => 1,
  'code' => 'B189',
  'name' => 'BANK BCA 069 - 5511189',
  'bank_id' => 1,
  'currency_id' => 1,
  'coa_code' => '102-008',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 2,
  'code' => 'B888',
  'name' => 'BANK BCA 069 - 3151888',
  'bank_id' => 1,
  'currency_id' => 1,
  'coa_code' => '102-002',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 3,
  'code' => 'B899',
  'name' => 'BANK BCA 069 - 3778899',
  'bank_id' => 1,
  'currency_id' => 1,
  'coa_code' => '102-001',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 4,
  'code' => 'B907',
  'name' => 'BANK BCA 069 - 1199907',
  'bank_id' => 1,
  'currency_id' => 1,
  'coa_code' => '102-010',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 5,
  'code' => 'B999',
  'name' => 'BANK BCA 069 - 3589999',
  'bank_id' => 1,
  'currency_id' => 1,
  'coa_code' => '102-003',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 6,
  'code' => 'M005',
  'name' => 'BANK MANDIRI 168-0037788005 (USD]',
  'bank_id' => 2,
  'currency_id' => 2,
  'coa_code' => '102-007',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 7,
  'code' => 'M038',
  'name' => 'BANK MANDIRI 168-0002954038',
  'bank_id' => 2,
  'currency_id' => 1,
  'coa_code' => '102-011',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 8,
  'code' => 'N006',
  'name' => 'BANK BNI 071-017-83006',
  'bank_id' => 3,
  'currency_id' => 1,
  'coa_code' => '102-006',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 9,
  'code' => 'P227',
  'name' => 'BANK PANIN 062-5000-227',
  'bank_id' => 4,
  'currency_id' => 1,
  'coa_code' => '102-004',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 10,
  'code' => 'P727',
  'name' => 'BANK PANIN USD - 1156000727',
  'bank_id' => 4,
  'currency_id' => 2,
  'coa_code' => '102-005',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 11,
  'code' => 'P888',
  'name' => 'BANK PANIN 115-5857-888',
  'bank_id' => 4,
  'currency_id' => 1,
  'coa_code' => '102-009',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
