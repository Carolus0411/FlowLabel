<?php

namespace Database\Seeders;

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = SimpleExcelReader::create(__DIR__.'/data/Currency.xlsx')->getRows();
        $rows->each(function(array $row) {

            $data['code'] = $row['code'];
            $data['name'] = $row['name'];
            $data['is_active'] = $row['is_active'];

            Currency::create($data);
        });
    }
}
