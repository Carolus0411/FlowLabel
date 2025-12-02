<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\PurchaseInvoiceClosed;
use App\Helpers\Code;
use App\Models\Coa;
use App\Models\Journal;

class PurchaseInvoiceCreateJournal
{
    public function __construct()
    {
        //
    }

    public function handle(PurchaseInvoiceClosed $purchaseInvoiceClosed): void
    {
        $purchaseInvoice = $purchaseInvoiceClosed->purchaseInvoice;
        $purchaseInvoice->load(['details.serviceCharge','details.serviceCharge.coaBuying','supplier']);

        $code = Code::auto('JV', $purchaseInvoice->invoice_date);

        $purchaseInvoice->update([
            'balance_amount' => $purchaseInvoice->invoice_amount,
            'status' => 'close'
        ]);

        // Journal header
        $journal = Journal::create([
            'code' => $code,
            'date' => $purchaseInvoice->invoice_date,
            'note' => $purchaseInvoice->note,
            'type' => 'general',
            'debit_total' => $purchaseInvoice->invoice_amount,
            'credit_total' => $purchaseInvoice->invoice_amount,
            'contact_id' => $purchaseInvoice->supplier_id,
            'ref_name' => class_basename($purchaseInvoice),
            'ref_id' => $purchaseInvoice->code,
            'status' => 'close',
            'saved' => '1',
        ]);

        // VAT IN (if any) - debit
        $vatInCode = settings('vat_in_code') ?: settings('vat_out_code');
        if ($purchaseInvoice->ppn_amount > 0) {
            $journal->details()->create([
                'coa_code' => $vatInCode,
                'description' => $purchaseInvoice->supplier->name ?? '',
                'dc' => 'D',
                'debit' => $purchaseInvoice->ppn_amount,
                'credit' => 0,
                'date' => $purchaseInvoice->invoice_date,
            ]);
        }

        // Stamp duty - debit
        if ($purchaseInvoice->stamp_amount > 0) {
            $journal->details()->create([
                'coa_code' => settings('stamp_code'),
                'description' => $purchaseInvoice->supplier->name ?? '',
                'dc' => 'D',
                'debit' => $purchaseInvoice->stamp_amount,
                'credit' => 0,
                'date' => $purchaseInvoice->invoice_date,
            ]);
        }

        // Service Charge (debit)
        foreach ($purchaseInvoice->details as $detail) {
            $journal->details()->create([
                'coa_code' => $detail->serviceCharge->coaBuying->code,
                'description' => $purchaseInvoice->supplier->name ?? '',
                'dc' => 'D',
                'debit' => $detail->amount,
                'credit' => 0,
                'date' => $purchaseInvoice->invoice_date,
            ]);
        }

        // Account payable (credit)
        $journal->details()->create([
            'coa_code' => settings('account_payable_code'),
            'description' => $purchaseInvoice->supplier->name ?? '',
            'dc' => 'C',
            'debit' => 0,
            'credit' => $purchaseInvoice->invoice_amount,
            'date' => $purchaseInvoice->invoice_date,
        ]);
    }
}
