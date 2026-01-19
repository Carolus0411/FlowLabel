<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceCharge;

// Simulate the searchServiceCharge call with empty filters (like on page load)
$transport = '';
$service_type = '';
$value = '';

$query = ServiceCharge::query()
    ->where(function ($q) use ($value) {
        $q->filterLike('code', $value);
        $q->orFilterLike('name', $value);
    })
    ->whereIn('transport', [$transport,''])
    ->whereIn('type', [$service_type,''])
    ->isActive();

echo "Query count: ".$query->count()."\n";
echo "Sample results (first 5):\n";
foreach ($query->limit(5)->get() as $sc) {
    echo "  {$sc->code}: transport={$sc->transport}, type={$sc->type}, group_id={$sc->service_charge_group_id}\n";
}
