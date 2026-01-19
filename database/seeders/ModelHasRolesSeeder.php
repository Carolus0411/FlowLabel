<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelHasRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('model_has_roles')->truncate();
        
        DB::table('model_has_roles')->insert([
            [
  'role_id' => 1,
  'model_type' => 'App\\Models\\User',
  'model_id' => 1,
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
