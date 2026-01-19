<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            RolesSeeder::class,
            PermissionsSeeder::class,
            ModelHasRolesSeeder::class,
            CompaniesSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
