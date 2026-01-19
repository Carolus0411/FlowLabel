<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OtherPayableInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Journal;

$inv = OtherPayableInvoice::where('code', 'OP/25/12/0003')->first();

if ($inv) {
    echo "=== INVOICE DETAILS ===\n";
    echo "Invoice Code: " . $inv->code . "\n";
    echo "Invoice Amount: " . $inv->invoice_amount . "\n";
    echo "DPP: " . $inv->dpp_amount . "\n";
    echo "PPN: " . $inv->ppn_amount . "\n";
    echo "PPH: " . $inv->pph_amount . "\n";
    echo "PPH Name: " . ($inv->pph->name ?? '-') . "\n";
    // determine pph coa used
    $pphCoa = settings('pph_code') ?: '203-002';
    if (!empty($inv->pph->name)) {
        if (str_contains($inv->pph->name, '21')) {
            $pphCoa = '203-001';
        } elseif (str_contains($inv->pph->name, '23')) {
            $pphCoa = '203-002';
        }
    }
    echo "Derived PPH COA: " . $pphCoa . "\n";
    echo "Stamp: " . $inv->stamp_amount . "\n";

    $journal = Journal::where('ref_name', 'OtherPayableInvoice')
        ->where('ref_id', 'OP/25/12/0003')
        ->first();

    if ($journal) {
        echo "\n=== JOURNAL ENTRIES ===\n";
        echo "Journal Code: " . $journal->code . "\n";
        echo "Debit Total: " . $journal->debit_total . "\n";
        echo "Credit Total: " . $journal->credit_total . "\n";

        echo "\nDETAILS:\n";
        foreach ($journal->details as $detail) {
            echo "  COA: " . $detail->coa_code . " | DC: " . $detail->dc . " | Debit: " . $detail->debit . " | Credit: " . $detail->credit . "\n";
        }
    }
}

// Sample purchase invoices with PPH
echo "\n=== PURCHASE INVOICE WITH PPH > 0 ===\n";
$purch = PurchaseInvoice::where('pph_amount', '>', 0)->first();
if ($purch) {
    echo "Purchase Code: " . $purch->code . "\n";
    echo "PPH: " . $purch->pph_amount . "\n";
    echo "PPH Name: " . ($purch->pph->name ?? '-') . "\n";
    $pphCoa = settings('pph_code') ?: '203-002';
    if (!empty($purch->pph->name)) {
        if (str_contains($purch->pph->name, '21')) {
            $pphCoa = '203-001';
        } elseif (str_contains($purch->pph->name, '23')) {
            $pphCoa = '203-002';
        }
    }
    echo "Derived PPH COA: " . $pphCoa . "\n";
    $journal = Journal::where('ref_name', 'PurchaseInvoice')->where('ref_id', $purch->code)->first();
    if ($journal) {
        echo "Journal: " . $journal->code . "\n";
        foreach ($journal->details as $detail) {
            echo "  COA: " . $detail->coa_code . " | DC: " . $detail->dc . " | Debit: " . $detail->debit . " | Credit: " . $detail->credit . "\n";
        }
    }
}
