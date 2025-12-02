<?php

namespace App\Jobs;

use App\Helpers\Code;
use App\Models\BankOut;
use App\Models\Journal;
use App\Models\PrepaidAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BankOutApprove implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankOut $bankOut,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $this->bankOut->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $this->bankOut->date);

            // Check if any detail has Trade Payable account
            $accountPayable = settings('account_payable_code');
            $hasPayable = $this->bankOut->details->contains(function ($detail) use ($accountPayable) {
                return strtolower(trim($detail->coa_code)) === strtolower(trim($accountPayable));
            });

            // update bank out status
            $this->bankOut->update([
                'status' => 'close',
                'has_payable' => $hasPayable ? 1 : 0,
            ]);

            // Create Prepaid Account entries for prepaid COA codes
            $prepaidCoaCodes = PrepaidAccount::getPrepaidCoaCodes();
            foreach ($this->bankOut->details as $detail) {
                if (in_array($detail->coa_code, $prepaidCoaCodes)) {
                    $prepaidCode = Code::auto('PA', $this->bankOut->date);
                    PrepaidAccount::create([
                        'code' => $prepaidCode,
                        'date' => $this->bankOut->date,
                        'coa_code' => $detail->coa_code,
                        'source_type' => 'BankOut',
                        'source_code' => $this->bankOut->code,
                        'contact_id' => $this->bankOut->contact_id,
                        'supplier_id' => $this->bankOut->supplier_id,
                        'debit' => $detail->amount,
                        'credit' => 0,
                        'note' => $this->bankOut->note,
                    ]);
                }
            }

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $this->bankOut->date,
                'note' => $this->bankOut->note,
                'type' => 'general',
                'debit_total' => $this->bankOut->total_amount,
                'credit_total' => $this->bankOut->total_amount,
                'contact_id' => $this->bankOut->contact_id,
                'ref_name' => class_basename($this->bankOut),
                'ref_id' => $this->bankOut->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            // Journal Details (Bank account on credit)
            $journal->details()->create([
                'coa_code' => $this->bankOut->bankAccount->coa->code,
                'description' => $this->bankOut->note,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $this->bankOut->total_amount,
                'date' => $this->bankOut->date,
            ]);

            // Bank Details on debit
            foreach ($this->bankOut->details as $detail) {

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $this->bankOut->note,
                    'dc' => 'D',
                    'debit' => $detail->amount,
                    'credit' => 0,
                    'date' => $this->bankOut->date,
                ]);
            }

        });
    }
}
