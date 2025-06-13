<?php

namespace Database\Seeders;

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Seeder;
use App\Models\CashAccount;

class CashAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = SimpleExcelReader::create(__DIR__.'/data/CashAccount.xlsx')->getRows();
        $rows->each(function(array $row) {

            $data['code'] = $row['code'];
            $data['name'] = $row['name'];
            $data['currency_id'] = $row['currency_id'];
            $data['coa_code'] = $row['coa_code'];
            $data['is_active'] = $row['is_active'];

            CashAccount::create($data);
        });
    }
}
