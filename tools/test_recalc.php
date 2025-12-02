<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;

$invoice = PurchaseInvoice::query()->first();
echo "Before: " . ($invoice->balance_amount ?? 'NULL') . PHP_EOL;
$invoice->update(['balance_amount' => DB::raw('balance_amount - 1')]);
try {
    $invoice->recalcPaymentStatus();
    echo "After: " . ($invoice->balance_amount ?? 'NULL') . PHP_EOL;
    // Test SalesInvoice similarly
    $sinv = SalesInvoice::query()->first();
    echo "Sales Before: " . ($sinv->balance_amount ?? 'NULL') . PHP_EOL;
    $sinv->update(['balance_amount' => DB::raw('balance_amount - 1')]);
    try {
        $sinv->recalcPaymentStatus();
        echo "Sales After: " . ($sinv->balance_amount ?? 'NULL') . PHP_EOL;
    } catch (\Throwable $ex) {
        echo "Sales Error: " . $ex->getMessage() . PHP_EOL;
    }
} catch (\Throwable $ex) {
    echo "Error: " . $ex->getMessage() . PHP_EOL;
}
