<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PpnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('ppn')->truncate();
        
        DB::table('ppn')->insert([
            [
  'id' => 1,
  'name' => 'PPN0',
  'value' => 0,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 2,
  'name' => 'PPN1',
  'value' => 1,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 3,
  'name' => 'PPN1.1',
  'value' => 1.1,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 4,
  'name' => 'PPN1.2',
  'value' => 1.2,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 5,
  'name' => 'PPN10',
  'value' => 10,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 6,
  'name' => 'PPN11',
  'value' => 11,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 7,
  'name' => 'PPN0',
  'value' => 0,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 8,
  'name' => 'PPN1',
  'value' => 1,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 9,
  'name' => 'PPN1.1',
  'value' => 1.1,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 10,
  'name' => 'PPN1.2',
  'value' => 1.2,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 11,
  'name' => 'PPN10',
  'value' => 10,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 12,
  'name' => 'PPN11',
  'value' => 11,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
