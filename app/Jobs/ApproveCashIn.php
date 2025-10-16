<?php

namespace App\Jobs;

use App\Helpers\Code;
use App\Models\CashIn;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ApproveCashIn implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CashIn $cashIn,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $this->cashIn->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $this->cashIn->date);

            // has receivable?
            $has_receivable = 0;
            $accountReceivable = settings('account_receivable_code');
            if ($this->cashIn->details()->where('coa_code', $accountReceivable)->exists()) {
                $has_receivable = 1;
            }

            // has prepaid?
            $has_prepaid = 0;
            $ARPrepaid = settings('ar_prepaid_code');
            if ($this->cashIn->details()->where('coa_code', $ARPrepaid)->exists()) {
                $has_prepaid = 1;
            }

            // update cash in status
            $this->cashIn->update([
                'has_receivable' => $has_receivable,
                'has_prepaid' => $has_prepaid,
                'status' => 'close'
            ]);

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $this->cashIn->date,
                'note' => $this->cashIn->note,
                'type' => 'general',
                'debit_total' => $this->cashIn->total_amount,
                'credit_total' => $this->cashIn->total_amount,
                'contact_id' => $this->cashIn->contact_id,
                'ref_name' => class_basename($this->cashIn),
                'ref_id' => $this->cashIn->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            // Journal Details
            $journal->details()->create([
                'coa_code' => $this->cashIn->cashAccount->coa->code,
                'description' => $this->cashIn->note,
                'dc' => 'D',
                'debit' => $this->cashIn->total_amount,
                'credit' => 0,
                'date' => $this->cashIn->date,
            ]);

            // Cash Details
            foreach ($this->cashIn->details as $detail) {

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $this->cashIn->note,
                    'dc' => 'C',
                    'debit' => 0,
                    'credit' => $detail->amount,
                    'date' => $this->cashIn->date,
                ]);
            }

        });
    }
}
