<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\OtherPayableInvoiceClosed;
use App\Helpers\Code;
use App\Models\Coa;
use App\Models\Journal;

class OtherPayableInvoiceCreateJournal
{
    public function __construct()
    {
        //
    }

    public function handle(OtherPayableInvoiceClosed $otherPayableInvoiceClosed): void
    {
        $invoice = $otherPayableInvoiceClosed->otherPayableInvoice;
        $invoice->load(['details.serviceCharge.coaBuying','details.pph','supplier']);

        $code = Code::auto('JV', $invoice->invoice_date);

        $invoice->update([
            'balance_amount' => $invoice->invoice_amount,
            'status' => 'close'
        ]);

        // Calculate total PPH from details grouped by type
        $pph21Total = 0;
        $pph23Total = 0;
        foreach ($invoice->details as $detail) {
            if ($detail->pph_amount > 0 && $detail->pph) {
                if (str_contains($detail->pph->name, '21')) {
                    $pph21Total += $detail->pph_amount;
                } elseif (str_contains($detail->pph->name, '23')) {
                    $pph23Total += $detail->pph_amount;
                }
            }
        }
        $totalPphAmount = $pph21Total + $pph23Total;

        // Journal header
        $journal = Journal::create([
            'code' => $code,
            'date' => $invoice->invoice_date,
            'note' => $invoice->note,
            'type' => 'general',
            'debit_total' => $invoice->invoice_amount,
            'credit_total' => $invoice->invoice_amount,
            'contact_id' => null,
            'supplier_id' => $invoice->supplier_id,
            'ref_name' => class_basename($invoice),
            'ref_id' => $invoice->code,
            'status' => 'close',
            'saved' => '1',
        ]);

        // VAT IN (if any) - debit
        $vatInCode = settings('vat_in_code') ?: settings('vat_out_code');
        if ($invoice->ppn_amount > 0) {
            $journal->details()->create([
                'coa_code' => $vatInCode,
                'description' => $invoice->note ?: ($invoice->supplier->name ?? ''),
                'dc' => 'D',
                'debit' => $invoice->ppn_amount,
                'credit' => 0,
                'date' => $invoice->invoice_date,
            ]);
        }


        // Stamp duty - debit
        if ($invoice->stamp_amount > 0) {
            $journal->details()->create([
                'coa_code' => settings('stamp_code'),
                'description' => $invoice->note ?: ($invoice->supplier->name ?? ''),
                'dc' => 'D',
                'debit' => $invoice->stamp_amount,
                'credit' => 0,
                'date' => $invoice->invoice_date,
            ]);
        }

        // Service Charge (debit) and PPH per detail (credit)
        foreach ($invoice->details as $detail) {
            $journal->details()->create([
                'coa_code' => $detail->serviceCharge->coaBuying->code,
                'description' => $detail->note ?: $invoice->note ?: ($invoice->supplier->name ?? ''),
                'dc' => 'D',
                'debit' => $detail->amount,
                'credit' => 0,
                'date' => $invoice->invoice_date,
            ]);

            // If detail contains PPH amount, create a PPH journal detail (credit)
            if ($detail->pph_amount > 0 && $detail->pph) {
                $pphCoa = settings('pph_code') ?: '203-002';
                if (!empty($detail->pph->name)) {
                    if (str_contains($detail->pph->name, '21')) {
                        $pphCoa = '203-001';
                    } elseif (str_contains($detail->pph->name, '23')) {
                        $pphCoa = '203-002';
                    }
                }

                $journal->details()->create([
                    'coa_code' => $pphCoa,
                    'description' => $detail->note ?: $invoice->note ?: ($invoice->supplier->name ?? ''),
                    'dc' => 'C',
                    'debit' => 0,
                    'credit' => $detail->pph_amount,
                    'date' => $invoice->invoice_date,
                ]);
            }
        }

        // Accrued Expenses (credit) - using 205-002 for Other Payable
        // Amount = invoice_amount - total_pph_amount (since PPH is a separate credit)
        $accruedExpensesAmount = $invoice->invoice_amount - $totalPphAmount;
        $journal->details()->create([
            'coa_code' => settings('accrued_expenses_code') ?: '205-002',
            'description' => $invoice->note ?: ($invoice->supplier->name ?? ''),
            'dc' => 'C',
            'debit' => 0,
            'credit' => $accruedExpensesAmount,
            'date' => $invoice->invoice_date,
        ]);
    }
}

