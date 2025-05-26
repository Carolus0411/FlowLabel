<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = SimpleExcelReader::create(__DIR__.'/data/Permissions.xlsx')->getRows();
        $rows->each(function(array $row) {

            $data['resource'] = strtolower($row['resource']);
            $data['name'] = strtolower($row['name']);

            Permission::create($data);
        });
    }
}
