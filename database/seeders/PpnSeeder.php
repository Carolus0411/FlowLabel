<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ppn;

class PpnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ppn::create([
            'name' => 'PPN0',
            'value' => 0,
            'is_active' => 1,
        ]);

        Ppn::create([
            'name' => 'PPN1',
            'value' => 1,
            'is_active' => 1,
        ]);

        Ppn::create([
            'name' => 'PPN1.1',
            'value' => 1.1,
            'is_active' => 1,
        ]);

        Ppn::create([
            'name' => 'PPN1.2',
            'value' => 1.2,
            'is_active' => 1,
        ]);

        Ppn::create([
            'name' => 'PPN10',
            'value' => 10,
            'is_active' => 1,
        ]);

        Ppn::create([
            'name' => 'PPN11',
            'value' => 11,
            'is_active' => 1,
        ]);
    }
}
