<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('item_type')->truncate();
        
        DB::table('item_type')->insert([
            [
  'id' => 1,
  'name' => 'Stock',
  'is_active' => 1,
  'created_at' => '2025-12-08 08:38:22',
  'updated_at' => '2025-12-09 01:20:51',
  'is_stock' => 1,
],
            [
  'id' => 2,
  'name' => 'Non Stock',
  'is_active' => 1,
  'created_at' => '2025-12-08 08:38:31',
  'updated_at' => '2025-12-08 08:38:31',
  'is_stock' => 0,
],
            [
  'id' => 3,
  'name' => 'Service',
  'is_active' => 1,
  'created_at' => '2025-12-08 08:38:44',
  'updated_at' => '2025-12-08 08:38:44',
  'is_stock' => 0,
],
            [
  'id' => 4,
  'name' => 'Raw Material',
  'is_active' => 1,
  'created_at' => '2025-12-09 02:10:59',
  'updated_at' => '2025-12-09 03:09:44',
  'is_stock' => 1,
],
            [
  'id' => 5,
  'name' => 'Finished Product',
  'is_active' => 1,
  'created_at' => '2025-12-09 03:10:27',
  'updated_at' => '2025-12-09 03:10:53',
  'is_stock' => 1,
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
