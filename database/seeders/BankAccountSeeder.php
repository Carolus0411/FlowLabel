<?php

namespace Database\Seeders;

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Seeder;
use App\Models\BankAccount;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = SimpleExcelReader::create(__DIR__.'/data/BankAccount.xlsx')->getRows();
        $rows->each(function(array $row) {

            $data['code'] = $row['code'];
            $data['name'] = $row['name'];
            $data['bank_id'] = $row['bank_id'];
            $data['currency_id'] = $row['currency_id'];
            $data['coa_code'] = $row['coa_code'];
            $data['is_active'] = $row['is_active'];

            BankAccount::create($data);
        });
    }
}
