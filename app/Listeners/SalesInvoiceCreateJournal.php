<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SalesInvoiceClosed;
use App\Helpers\Code;
use App\Models\Coa;
use App\Models\Journal;

class SalesInvoiceCreateJournal
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
    public function handle(SalesInvoiceClosed $salesInvoiceClosed): void
    {
        $salesInvoice = $salesInvoiceClosed->salesInvoice;
        $salesInvoice->load(['details.serviceCharge','details.serviceCharge.coaSelling']);

        $code = Code::auto('JV', $salesInvoice->invoice_date);

        $salesInvoice->update([
            'balance_amount' => $salesInvoice->invoice_amount,
            'status' => 'close'
        ]);

        // Journal header
        $journal = Journal::create([
            'code' => $code,
            'date' => $salesInvoice->invoice_date,
            'note' => $salesInvoice->note,
            'type' => 'general',
            'debit_total' => $salesInvoice->invoice_amount,
            'credit_total' => $salesInvoice->invoice_amount,
            'contact_id' => $salesInvoice->contact_id,
            'ref_name' => class_basename($salesInvoice),
            'ref_id' => $salesInvoice->code,
            'status' => 'close',
            'saved' => '1',
        ]);

        // Account receivable
        $journal->details()->create([
            'coa_code' => settings('account_receivable_code'),
            'description' => $salesInvoice->contact->name,
            'dc' => 'D',
            'debit' => $salesInvoice->invoice_amount,
            'credit' => 0,
            'date' => $salesInvoice->invoice_date,
        ]);

        // Vat out
        if ($salesInvoice->ppn_amount > 0) {
            $journal->details()->create([
                'coa_code' => settings('vat_out_code'),
                'description' => $salesInvoice->contact->name,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $salesInvoice->ppn_amount,
                'date' => $salesInvoice->invoice_date,
            ]);
        }

        // Stamp duty
        if ($salesInvoice->stamp_amount > 0) {
            $journal->details()->create([
                'coa_code' => settings('stamp_code'),
                'description' => $salesInvoice->contact->name,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $salesInvoice->stamp_amount,
                'date' => $salesInvoice->invoice_date,
            ]);
        }

        // Service Charge
        foreach ($salesInvoice->details as $detail) {
            $journal->details()->create([
                'coa_code' => $detail->serviceCharge->coaSelling->code,
                'description' => $salesInvoice->contact->name,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $detail->amount,
                'date' => $salesInvoice->invoice_date,
            ]);
        }
    }
}
