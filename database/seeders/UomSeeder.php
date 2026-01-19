<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('uom')->truncate();
        
        DB::table('uom')->insert([
            [
  'id' => 1,
  'code' => 'KGS',
  'name' => 'Kilograms',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 2,
  'code' => 'M3',
  'name' => 'M3',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 3,
  'code' => 'PCS',
  'name' => 'PCS',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 4,
  'code' => 'DOC',
  'name' => 'DOC',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 5,
  'code' => 'BLN',
  'name' => 'BULAN',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 6,
  'code' => 'CBM',
  'name' => 'KUBIK',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 7,
  'code' => 'DAY',
  'name' => 'HARI',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 8,
  'code' => 'HOUR',
  'name' => 'JAM',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 9,
  'code' => 'UNIT',
  'name' => 'UNIT',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
],
            [
  'id' => 10,
  'code' => 'M2',
  'name' => 'M2',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:51',
  'updated_at' => '2025-12-03 09:43:51',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
