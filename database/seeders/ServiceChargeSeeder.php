<?php

namespace Database\Seeders;

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Seeder;
use App\Models\Coa;
use App\Models\ServiceCharge;

class ServiceChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = SimpleExcelReader::create(__DIR__.'/data/Service Charge.xlsx')->getRows();
        $rows->each(function(array $row) {

            $buying_coa_id = 0;
            $selling_coa_id = 0;

            if (!empty($row['coa_buying'])) {
                $buying_coa_id = Coa::where('code', $row['coa_buying'])->first()->id ?? 0;
            }

            if (!empty($row['coa_selling'])) {
                $selling_coa_id = Coa::where('code', $row['coa_selling'])->first()->id ?? 0;
            }

            $data['code'] = $row['code'];
            $data['name'] = $row['name'];
            $data['type'] = $row['type'];
            $data['buying_coa_id'] = $buying_coa_id;
            $data['selling_coa_id'] = $selling_coa_id;
            $data['is_active'] = $row['is_active'];

            ServiceCharge::create($data);
        });
    }
}
