<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('currency')->truncate();
        
        DB::table('currency')->insert([
            [
  'id' => 1,
  'code' => 'IDR',
  'name' => 'Indonesia Rupiah',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 2,
  'code' => 'USD',
  'name' => 'US Dollar',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 3,
  'code' => 'RMB',
  'name' => 'Renmimbi',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 4,
  'code' => 'THB',
  'name' => 'Baht Thailand',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 5,
  'code' => 'SGD',
  'name' => 'Singapore Dollar',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 6,
  'code' => 'AUD',
  'name' => 'Australia Dollar',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 7,
  'code' => 'EUR',
  'name' => 'Euro',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 8,
  'code' => 'HKD',
  'name' => 'Hongkong Dollar',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
