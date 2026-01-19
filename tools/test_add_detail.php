<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OtherPayableInvoice;

$invoice = OtherPayableInvoice::find(6);
if (! $invoice) {
    echo 'Invoice not found';
    exit(1);
}

try {
    $detail = $invoice->details()->create([
        'service_charge_id' => 193,
        'note' => 'Test note',
        'uom_id' => 5,
        'currency_id' => 1,
        'currency_rate' => 1,
        'qty' => 1,
        'price' => 500000,
        'foreign_amount' => 500000,
        'amount' => 500000,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    echo "Created detail " . $detail->id . PHP_EOL;
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage();
}
