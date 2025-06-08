<?php

namespace Database\Seeders;

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = SimpleExcelReader::create(__DIR__.'/data/Bank.xlsx')->getRows();
        $rows->each(function(array $row) {

            $data['name'] = $row['name'];
            $data['is_active'] = $row['is_active'];

            Bank::create($data);
        });
    }
}
