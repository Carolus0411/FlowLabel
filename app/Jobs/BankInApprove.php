<?php

namespace App\Jobs;

use App\Helpers\Code;
use App\Models\BankIn;
use App\Models\Journal;
use App\Models\PrepaidAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BankInApprove implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankIn $bankIn,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $this->bankIn->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $this->bankIn->date);

            // has receivable?
            $has_receivable = 0;
            $accountReceivable = settings('account_receivable_code');
            if ($this->bankIn->details()->where('coa_code', $accountReceivable)->exists()) {
                $has_receivable = 1;
            }

            // has prepaid?
            $has_prepaid = 0;
            $ARPrepaid = settings('ar_prepaid_code');
            if ($this->bankIn->details()->where('coa_code', $ARPrepaid)->exists()) {
                $has_prepaid = 1;
            }

            // update bank in status
            $this->bankIn->update([
                'has_receivable' => $has_receivable,
                'has_prepaid' => $has_prepaid,
                'status' => 'close'
            ]);

            // Create Prepaid Account entries for prepaid COA codes
            $prepaidCoaCodes = PrepaidAccount::getPrepaidCoaCodes();
            foreach ($this->bankIn->details as $detail) {
                if (in_array($detail->coa_code, $prepaidCoaCodes)) {
                    $prepaidCode = Code::auto('PA', $this->bankIn->date);
                    PrepaidAccount::create([
                        'code' => $prepaidCode,
                        'date' => $this->bankIn->date,
                        'coa_code' => $detail->coa_code,
                        'source_type' => 'BankIn',
                        'source_code' => $this->bankIn->code,
                        'contact_id' => $this->bankIn->contact_id,
                        'supplier_id' => null,
                        'debit' => 0,
                        'credit' => $detail->amount,
                        'note' => $this->bankIn->note,
                    ]);
                }
            }

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $this->bankIn->date,
                'note' => $this->bankIn->note,
                'type' => 'general',
                'debit_total' => $this->bankIn->total_amount,
                'credit_total' => $this->bankIn->total_amount,
                'contact_id' => $this->bankIn->contact_id,
                'ref_name' => class_basename($this->bankIn),
                'ref_id' => $this->bankIn->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            // Journal Details
            $journal->details()->create([
                'coa_code' => $this->bankIn->bankAccount->coa->code,
                'description' => $this->bankIn->note,
                'dc' => 'D',
                'debit' => $this->bankIn->total_amount,
                'credit' => 0,
                'date' => $this->bankIn->date,
            ]);

            // Bank Details
            foreach ($this->bankIn->details as $detail) {

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $this->bankIn->note,
                    'dc' => 'C',
                    'debit' => 0,
                    'credit' => $detail->amount,
                    'date' => $this->bankIn->date,
                ]);
            }

        });
    }
}
