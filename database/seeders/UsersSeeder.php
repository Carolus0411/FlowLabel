<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Enums\ActiveStatus;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create admin user if doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'id' => 1,
                'name' => 'Admin',
                'password' => '$2y$12$btW/8XffgPdXgMt1rSDGTO/m/OXdzSydpY/9uGhboitZ8X0KHff/m',
                'remember_token' => 'I6xnJMMw6ZzypKEkKD76NOS2iDCdcz2FM2El60ngSjIVKuY5LfVzEwd65xkE',
                'status' => ActiveStatus::active,
                'role' => 'admin',
            ]
        );

        $this->command->info('✓ Admin user seeded');
    }
}
