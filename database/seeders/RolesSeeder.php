<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('roles')->truncate();
        
        DB::table('roles')->insert([
            [
  'id' => 1,
  'name' => 'admin',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:43',
  'updated_at' => '2025-12-03 09:43:43',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
