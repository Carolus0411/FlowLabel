<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\ServiceCharge;

$count = ServiceCharge::count();
$groupCount = ServiceCharge::whereNotNull('service_charge_group_id')->count();
echo 'Count: '.$count.PHP_EOL;
echo 'Grouped: '.$groupCount.PHP_EOL;
