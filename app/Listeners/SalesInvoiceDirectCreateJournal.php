<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SalesInvoiceDirectClosed;
use App\Helpers\Code;
use App\Models\Coa;
use App\Models\Journal;

class SalesInvoiceDirectCreateJournal
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SalesInvoiceDirectClosed $salesInvoiceDirectClosed): void
    {
        $salesInvoiceDirect = $salesInvoiceDirectClosed->salesInvoiceDirect;
        $salesInvoiceDirect->load(['details.serviceCharge','details.serviceCharge.coaSelling','contact']);

        $code = Code::auto('JV', $salesInvoiceDirect->invoice_date);

        $salesInvoiceDirect->update([
            'balance_amount' => $salesInvoiceDirect->invoice_amount,
            'status' => 'close'
        ]);

        // Journal header
        $journal = Journal::create([
            'code' => $code,
            'date' => $salesInvoiceDirect->invoice_date,
            'note' => $salesInvoiceDirect->note,
            'type' => 'general',
            'debit_total' => $salesInvoiceDirect->invoice_amount,
            'credit_total' => $salesInvoiceDirect->invoice_amount,
            'contact_id' => $salesInvoiceDirect->contact_id,
            'ref_name' => class_basename($salesInvoiceDirect),
            'ref_id' => $salesInvoiceDirect->code,
            'status' => 'close',
            'saved' => '1',
        ]);

        // Account receivable
        $journal->details()->create([
            'coa_code' => settings('account_receivable_code'),
            'description' => $salesInvoiceDirect->contact->name,
            'dc' => 'D',
            'debit' => $salesInvoiceDirect->invoice_amount,
            'credit' => 0,
            'date' => $salesInvoiceDirect->invoice_date,
        ]);

        // Vat out
        if ($salesInvoiceDirect->ppn_amount > 0) {
            $journal->details()->create([
                'coa_code' => settings('vat_out_code'),
                'description' => $salesInvoiceDirect->contact->name,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $salesInvoiceDirect->ppn_amount,
                'date' => $salesInvoiceDirect->invoice_date,
            ]);
        }

        // Stamp duty
        if ($salesInvoiceDirect->stamp_amount > 0) {
            $journal->details()->create([
                'coa_code' => settings('stamp_code'),
                'description' => $salesInvoiceDirect->contact->name,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $salesInvoiceDirect->stamp_amount,
                'date' => $salesInvoiceDirect->invoice_date,
            ]);
        }

        // Service Charge
        foreach ($salesInvoiceDirect->details as $detail) {
            $journal->details()->create([
                'coa_code' => $detail->serviceCharge->coaSelling->code,
                'description' => $salesInvoiceDirect->contact->name,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $detail->amount,
                'date' => $salesInvoiceDirect->invoice_date,
            ]);
        }
    }
}
