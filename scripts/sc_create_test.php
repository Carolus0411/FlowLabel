<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Coa;
use App\Models\ServiceCharge;

$firstCoa = Coa::first();
if (!$firstCoa) {
    echo "No COA found. Can't create a ServiceCharge.\n";
    exit;
}

$data = [
    'code' => 'SC-OPT-'.time(),
    'name' => 'Test Optional Fields',
    'buying_coa_id' => $firstCoa->id,
    'selling_coa_id' => $firstCoa->id,
    'is_active' => 1,
    // 'transport' => null, // omit
    // 'type' => null, // omit
];

$sc = ServiceCharge::create($data);
echo "Created ServiceCharge id: {$sc->id}, code: {$sc->code}, transport: {$sc->transport}, type: {$sc->type}\n";

// cleanup
$sc->delete();
echo "Deleted Test record.\n";
