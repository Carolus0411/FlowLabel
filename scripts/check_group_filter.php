<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceCharge;
use App\Models\ServiceChargeGroup;

// Get OTPY group
$otpy = ServiceChargeGroup::where('code', 'OTPY')->first();
echo "OTPY group ID: ".$otpy->id."\n\n";

// Get the first OtherPayableInvoice to see what transport/type it uses
use App\Models\OtherPayableInvoice;
$invoice = OtherPayableInvoice::first();
if ($invoice) {
    echo "Sample OtherPayableInvoice:\n";
    echo "  transport: {$invoice->transport}\n";
    echo "  service_type: {$invoice->service_type}\n\n";

    // Search using the same logic as detail component
    $transport = $invoice->transport;
    $service_type = $invoice->service_type;

    $results = ServiceCharge::query()
        ->whereIn('transport', [$transport,''])
        ->whereIn('type', [$service_type,''])
        ->isActive()
        ->get();

    echo "ServiceCharges matching transport={$transport}, type={$service_type}:\n";
    echo "Total: ".$results->count()."\n";
    foreach ($results as $sc) {
        echo "  {$sc->code}: group_id={$sc->service_charge_group_id} ".($sc->service_charge_group_id == $otpy->id ? "(OTPY)" : "")."\n";
    }
}
