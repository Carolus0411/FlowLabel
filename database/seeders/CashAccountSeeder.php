<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('cash_account')->truncate();
        
        DB::table('cash_account')->insert([
            [
  'id' => 1,
  'code' => 'COHB',
  'name' => 'CASH ON HAND BDR',
  'currency_id' => 1,
  'coa_code' => '101-003',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 2,
  'code' => 'COHM',
  'name' => 'CASH ON HAND MKR',
  'currency_id' => 1,
  'coa_code' => '101-004',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 3,
  'code' => 'COHO',
  'name' => 'CASH ON HAND IDR',
  'currency_id' => 1,
  'coa_code' => '101-001',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 4,
  'code' => 'COHR',
  'name' => 'CASH ON HAND RMB',
  'currency_id' => 3,
  'coa_code' => '101-009',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
],
            [
  'id' => 5,
  'code' => 'COHU',
  'name' => 'CASH ON HAND USD',
  'currency_id' => 2,
  'coa_code' => '101-002',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:55',
  'updated_at' => '2025-12-03 09:43:55',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
