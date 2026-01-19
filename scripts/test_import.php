<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Coa;
use App\Models\ServiceCharge;
use App\Models\ServiceChargeGroup;

// Simulate a row from an import file
$rows = [
    ['code' => 'TMP-IMPORT-001', 'name' => 'Tmp Service 1', 'type' => 'import', 'transport' => 'sea', 'coa_buying' => '', 'coa_selling' => '', 'service_charge_group' => 'DEFAULT', 'is_active' => 1],
    ['code' => 'TMP-IMPORT-002', 'name' => 'Tmp Service 2', 'type' => 'export', 'transport' => 'air', 'coa_buying' => '', 'coa_selling' => '', 'service_charge_group' => 'FEE', 'is_active' => 1],
];

foreach ($rows as $row) {
    $buying_coa_id = 0;
    $selling_coa_id = 0;
    $service_charge_group_id = null;

    if (!empty($row['coa_buying'])) {
        $buying_coa_id = Coa::where('code', $row['coa_buying'])->first()->id ?? 0;
    }

    if (!empty($row['coa_selling'])) {
        $selling_coa_id = Coa::where('code', $row['coa_selling'])->first()->id ?? 0;
    }

    if (!empty($row['service_charge_group'])) {
        $g = ServiceChargeGroup::where('code', $row['service_charge_group'])->first();
        $service_charge_group_id = $g->id ?? null;
    }

    $data = [
        'code' => $row['code'],
        'name' => $row['name'],
        'type' => $row['type'],
        'transport' => $row['transport'],
        'buying_coa_id' => $buying_coa_id,
        'selling_coa_id' => $selling_coa_id,
        'is_active' => $row['is_active'],
        'service_charge_group_id' => $service_charge_group_id,
    ];

    ServiceCharge::updateOrCreate(['code' => $row['code']], $data);
}

echo "Done\n";
