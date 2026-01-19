<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ThreePlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $platforms = [
            ['id' => 1, 'name' => 'Lazada', 'code' => 'LZD', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Shopee', 'code' => 'SPE', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'TikTok', 'code' => 'TKT', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Tokopedia', 'code' => 'TPD', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Bukalapak', 'code' => 'BKL', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'Blibli', 'code' => 'BLB', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($platforms as $platform) {
            DB::table('three_pl')->updateOrInsert(
                ['id' => $platform['id']],
                $platform
            );
        }

        $this->command->info('✓ 3PL (Platform) data seeded successfully!');
        $this->command->info('  - Lazada (ID: 1)');
        $this->command->info('  - Shopee (ID: 2)');
        $this->command->info('  - TikTok (ID: 3)');
        $this->command->info('  - Tokopedia (ID: 4)');
        $this->command->info('  - Bukalapak (ID: 5)');
        $this->command->info('  - Blibli (ID: 6)');
    }
}
