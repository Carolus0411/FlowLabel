<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('companies')->truncate();
        
        DB::table('companies')->insert([
            [
  'id' => 1,
  'code' => 'Main',
  'name' => 'HycooFIN',
  'address' => 'Jl. Pluit Karang Selatan No.73, RT.7/RW.16, Pluit, Kecamatan Penjaringan, Jkt Utara, Daerah Khusus Ibukota Jakarta 14450',
  'phone' => '089687690907',
  'email' => 'carolus0411@gmail.com',
  'website' => 'http://hypercodesolutions.com/',
  'logo' => 'company-logos/p3rDGu5G04o2hLaS9BB2AykQIufRXxatBQtxFbhu.svg',
  'type' => 'main',
  'description' => '',
  'is_active' => 1,
  'created_at' => '2025-12-09 09:29:21',
  'updated_at' => '2025-12-10 06:44:08',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
