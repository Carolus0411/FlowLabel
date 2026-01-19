<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Helpers\Code;
use App\Models\OtherPayableInvoice;
use App\Models\OtherPayableInvoiceDetail;
use App\Models\Supplier;
use App\Models\ServiceCharge;
use App\Models\Pph;
use App\Events\OtherPayableInvoiceClosed;
use App\Models\Journal;

$supplier = Supplier::first();
$service = ServiceCharge::first();
$pph = Pph::where('name','like','%PPH 21%')->first();
if (!$pph) {
    echo "No PPH 21 found; check seeder." . PHP_EOL;
    exit(1);
}

$code = Code::auto('OP', date('Y-m-d'));
$invoice = OtherPayableInvoice::create([
    'code' => $code,
    'invoice_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'invoice_type' => 'OP',
    'note' => 'Test Other Payable PPH 21',
    'supplier_id' => $supplier->id,
    'top' => 30,
    'dpp_amount' => 200000,
    'ppn_id' => null,
    'ppn_amount' => 0,
    'pph_id' => $pph->id,
    'pph_amount' => round(($pph->value/100) * 200000, 2),
    'stamp_amount' => 0,
    'invoice_amount' => 200000,
    'balance_amount' => 200000,
    'saved' => 1,
    'status' => 'open',
    'created_by' => 1,
    'updated_by' => 1,
]);

OtherPayableInvoiceDetail::create([
    'other_payable_invoice_id' => $invoice->id,
    'service_charge_id' => $service->id,
    'qty' => 1,
    'price' => 200000,
    'amount' => 200000,
    'note' => 'Detail note: service x',
    'pph_id' => $pph->id,
    'pph_amount' => round(($pph->value/100) * 200000, 2),
]);

$user = \App\Models\User::first();
if ($user) {
    auth()->loginUsingId($user->id);
}

// Dispatch event to trigger journal creation
\App\Events\OtherPayableInvoiceClosed::dispatch($invoice);

// find journal
$journal = Journal::where('ref_name','OtherPayableInvoice')->where('ref_id',$invoice->code)->first();
if ($journal) {
    echo "Journal Code: {$journal->code}\n";
    foreach ($journal->details as $detail) {
        echo "COA: {$detail->coa_code} | DC: {$detail->dc} | Debit: {$detail->debit} | Credit: {$detail->credit} | Desc: {$detail->description}\n";
    }
} else {
    echo "No journal created.\n";
}

echo "Done.\n";
