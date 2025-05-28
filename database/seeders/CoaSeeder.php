<?php

namespace Database\Seeders;

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Seeder;
use App\Models\Coa;

class CoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = SimpleExcelReader::create(__DIR__.'/data/Coa.xlsx')->getRows();
        $rows->each(function(array $row) {

            $data['code'] = $row['code'];
            $data['name'] = $row['name'];
            $data['normal_balance'] = $row['normal_balance'];
            $data['report_type'] = $row['report_type'];
            $data['is_active'] = $row['is_active'];

            Coa::create($data);
        });
    }
}
