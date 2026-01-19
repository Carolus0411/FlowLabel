<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PphSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('pph')->truncate();
        
        DB::table('pph')->insert([
            [
  'id' => 1,
  'name' => 'PPH 23 0%',
  'value' => 0,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 2,
  'name' => 'PPH 23 2%',
  'value' => 2,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 3,
  'name' => 'PPH 23 10%',
  'value' => 10,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 4,
  'name' => 'PPH 21 5%',
  'value' => 5,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
],
            [
  'id' => 5,
  'name' => 'PPH 21',
  'value' => 2.5,
  'is_active' => 1,
  'created_at' => '2025-12-03 09:45:11',
  'updated_at' => '2025-12-03 09:45:24',
],
            [
  'id' => 6,
  'name' => 'PPH 23 0%',
  'value' => 0,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 7,
  'name' => 'PPH 23 2%',
  'value' => 2,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 8,
  'name' => 'PPH 23 10%',
  'value' => 10,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
],
            [
  'id' => 9,
  'name' => 'PPH 21 5%',
  'value' => 5,
  'is_active' => 1,
  'created_at' => '2025-12-09 04:06:38',
  'updated_at' => '2025-12-09 04:06:38',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
