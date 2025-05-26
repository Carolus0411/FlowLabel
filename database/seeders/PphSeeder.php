<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pph;

class PphSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pph::create([
            'name' => 'PPH 23 0%',
            'value' => 0,
            'is_active' => 1,
        ]);

        Pph::create([
            'name' => 'PPH 23 2%',
            'value' => 2,
            'is_active' => 1,
        ]);

        Pph::create([
            'name' => 'PPH 23 10%',
            'value' => 10,
            'is_active' => 1,
        ]);
    }
}
