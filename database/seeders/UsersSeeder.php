<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('users')->truncate();
        
        DB::table('users')->insert([
            [
  'id' => 1,
  'name' => 'Admin',
  'email' => 'admin@gmail.com',
  'email_verified_at' => NULL,
  'password' => '$2y$12$btW/8XffgPdXgMt1rSDGTO/m/OXdzSydpY/9uGhboitZ8X0KHff/m',
  'remember_token' => 'I6xnJMMw6ZzypKEkKD76NOS2iDCdcz2FM2El60ngSjIVKuY5LfVzEwd65xkE',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'avatar' => NULL,
  'role' => 'admin',
  'status' => 'active',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
