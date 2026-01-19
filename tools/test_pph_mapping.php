<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Helpers\Code;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Supplier;
use App\Models\ServiceCharge;
use App\Models\Pph;
use App\Events\PurchaseInvoiceClosed;
use App\Models\Journal;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

$supplier = Supplier::first();
$service = ServiceCharge::first();
$pph = Pph::where('name','like','%PPH 21%')->first();
if (!$pph) {
    echo "No PPH 21 found; check seeder." . PHP_EOL;
    exit(1);
}

$code = Code::auto('AP', date('Y-m-d'));
$invoice = PurchaseInvoice::create([
    'code' => $code,
    'invoice_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'invoice_type' => 'AP',
    'note' => 'Test PPH 21',
    'supplier_id' => $supplier->id,
    'top' => 30,
    'dpp_amount' => 1000000, // 1,000,000
    'ppn_id' => null,
    'ppn_amount' => 0,
    'pph_id' => $pph->id,
    'pph_amount' => round(($pph->value/100) * 1000000, 2),
    'stamp_amount' => 0,
    'invoice_amount' => 1000000,
    'balance_amount' => 1000000,
    'saved' => 1,
    'status' => 'open',
    'created_by' => 1,
    'updated_by' => 1,
]);

PurchaseInvoiceDetail::create([
    'purchase_invoice_id' => $invoice->id,
    'service_charge_id' => $service->id,
    'qty' => 1,
    'price' => 1000000,
    'amount' => 1000000,
]);

// Ensure we have an authenticated user because Journal model sets created_by from auth()->user()
$user = \App\Models\User::first();
if ($user) {
    auth()->loginUsingId($user->id);
}

// Dispatch event to trigger journal creation
\App\Events\PurchaseInvoiceClosed::dispatch($invoice);

// find journal
$journal = Journal::where('ref_name','PurchaseInvoice')->where('ref_id',$invoice->code)->first();
if ($journal) {
    echo "Journal Code: {$journal->code}\n";
    foreach ($journal->details as $detail) {
        echo "COA: {$detail->coa_code} | DC: {$detail->dc} | Debit: {$detail->debit} | Credit: {$detail->credit}\n";
    }
} else {
    echo "No journal created.\n";
}

echo "Done.\n";
