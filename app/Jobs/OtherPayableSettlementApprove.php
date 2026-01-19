<?php

namespace App\Jobs;

use App\Helpers\Code;
use App\Models\OtherPayableSettlement;
use App\Models\Journal;
use App\Models\PrepaidAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtherPayableSettlementApprove implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public OtherPayableSettlement $otherPayableSettlement,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $this->otherPayableSettlement->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $this->otherPayableSettlement->date);

            // Check if any detail has Accrued Expenses account
            $accruedExpensesCode = settings('accrued_expenses_code') ?: '205-002';
            $hasPayable = $this->otherPayableSettlement->details->contains(function ($detail) use ($accruedExpensesCode) {
                return strtolower(trim($detail->coa_code)) === strtolower(trim($accruedExpensesCode));
            });

            // update settlement status
            $this->otherPayableSettlement->update([
                'status' => 'close',
                'has_payable' => $hasPayable ? 1 : 0,
            ]);

            // Create Prepaid Account entries for prepaid COA codes
            $prepaidCoaCodes = PrepaidAccount::getPrepaidCoaCodes();
            foreach ($this->otherPayableSettlement->details as $detail) {
                if (in_array($detail->coa_code, $prepaidCoaCodes)) {
                    $prepaidCode = Code::auto('PA', $this->otherPayableSettlement->date);
                    PrepaidAccount::create([
                        'code' => $prepaidCode,
                        'date' => $this->otherPayableSettlement->date,
                        'coa_code' => $detail->coa_code,
                        'source_type' => 'OtherPayableSettlement',
                        'source_code' => $this->otherPayableSettlement->code,
                        'contact_id' => null,
                        'supplier_id' => $this->otherPayableSettlement->supplier_id,
                        'debit' => $detail->amount,
                        'credit' => 0,
                        'note' => $this->otherPayableSettlement->note,
                    ]);
                }
            }

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $this->otherPayableSettlement->date,
                'note' => $this->otherPayableSettlement->note,
                'type' => 'general',
                'debit_total' => $this->otherPayableSettlement->total_amount,
                'credit_total' => $this->otherPayableSettlement->total_amount,
                'contact_id' => null,
                'supplier_id' => $this->otherPayableSettlement->supplier_id,
                'ref_name' => class_basename($this->otherPayableSettlement),
                'ref_id' => $this->otherPayableSettlement->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            // Journal Details (Bank account on credit)
            $journal->details()->create([
                'coa_code' => $this->otherPayableSettlement->bankAccount->coa->code,
                'description' => $this->otherPayableSettlement->note,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $this->otherPayableSettlement->total_amount,
                'date' => $this->otherPayableSettlement->date,
            ]);

            // Settlement Details on debit
            foreach ($this->otherPayableSettlement->details as $detail) {

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $this->otherPayableSettlement->note,
                    'dc' => 'D',
                    'debit' => $detail->amount,
                    'credit' => 0,
                    'date' => $this->otherPayableSettlement->date,
                ]);
            }

        });
    }
}
