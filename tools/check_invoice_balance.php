<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchaseSettlement;
use App\Models\PurchaseInvoice;

$s = PurchaseSettlement::find(14);
echo "Settlement ID: " . $s->id . PHP_EOL;
echo "Supplier ID: " . $s->supplier_id . PHP_EOL;

$invoices = PurchaseInvoice::where('supplier_id', $s->supplier_id)
    ->where('balance_amount', '>', 0)
    ->get(['code', 'invoice_amount', 'balance_amount']);

echo "Invoices with balance > 0:" . PHP_EOL;
foreach ($invoices as $inv) {
    echo "  Code: {$inv->code}, Invoice: {$inv->invoice_amount}, Balance: {$inv->balance_amount}" . PHP_EOL;
}

// Also check all invoices for this supplier
echo PHP_EOL . "All invoices for this supplier:" . PHP_EOL;
$allInvoices = PurchaseInvoice::where('supplier_id', $s->supplier_id)->get(['code', 'invoice_amount', 'balance_amount', 'status']);
foreach ($allInvoices as $inv) {
    echo "  Code: {$inv->code}, Invoice: {$inv->invoice_amount}, Balance: {$inv->balance_amount}, Status: {$inv->status}" . PHP_EOL;
}
