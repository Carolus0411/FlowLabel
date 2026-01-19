<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceChargeGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('service_charge_group')->truncate();
        
        DB::table('service_charge_group')->insert([
            [
  'id' => 4,
  'code' => 'FOOD',
  'name' => 'FOOD',
  'is_active' => 1,
  'created_at' => '2025-12-03 09:46:39',
  'updated_at' => '2025-12-09 03:32:14',
],
            [
  'id' => 17,
  'code' => 'BEVR',
  'name' => 'BEVERAGE',
  'is_active' => 1,
  'created_at' => '2025-12-09 03:32:42',
  'updated_at' => '2025-12-09 03:32:42',
],
            [
  'id' => 18,
  'code' => 'RAWM',
  'name' => 'RAW MATERIAL',
  'is_active' => 1,
  'created_at' => '2025-12-09 03:32:56',
  'updated_at' => '2025-12-09 03:32:56',
],
            [
  'id' => 19,
  'code' => 'FNSG',
  'name' => 'FINISH GOODS',
  'is_active' => 1,
  'created_at' => '2025-12-09 03:33:22',
  'updated_at' => '2025-12-09 03:33:22',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
