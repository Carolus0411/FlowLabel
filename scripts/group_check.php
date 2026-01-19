<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceChargeGroup;
use App\Models\ServiceCharge;

echo "=== ServiceChargeGroup ===\n";
$groups = ServiceChargeGroup::all();
foreach ($groups as $group) {
    $count = ServiceCharge::where('service_charge_group_id', $group->id)->count();
    echo "{$group->code} (id: {$group->id}): {$count} service charges\n";
}

echo "\n=== ServiceCharge with group assigned ===\n";
$withGroup = ServiceCharge::whereNotNull('service_charge_group_id')->with('group')->limit(5)->get();
foreach ($withGroup as $sc) {
    echo "{$sc->code}: {$sc->group?->code}\n";
}

echo "\n=== Total without group ===\n";
echo ServiceCharge::whereNull('service_charge_group_id')->count()."\n";
