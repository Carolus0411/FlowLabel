<?php

namespace App\Jobs;

use App\Helpers\Code;
use App\Models\CashOut;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CashOutApprove implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CashOut $cashOut,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $this->cashOut->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $this->cashOut->date);

            // cash out status
            $this->cashOut->update([
                'status' => 'close'
            ]);

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $this->cashOut->date,
                'note' => $this->cashOut->note,
                'type' => 'general',
                'debit_total' => $this->cashOut->total_amount,
                'credit_total' => $this->cashOut->total_amount,
                'contact_id' => $this->cashOut->contact_id,
                'ref_name' => class_basename($this->cashOut),
                'ref_id' => $this->cashOut->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            // Details on credit side
            $journal->details()->create([
                'coa_code' => $this->cashOut->cashAccount->coa->code,
                'description' => $this->cashOut->note,
                'dc' => 'C',
                'debit' => 0,
                'credit' => $this->cashOut->total_amount,
                'date' => $this->cashOut->date,
            ]);

            // Details in debit side
            foreach ($this->cashOut->details as $detail) {

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $this->cashOut->note,
                    'dc' => 'D',
                    'debit' => $detail->amount,
                    'credit' => 0,
                    'date' => $this->cashOut->date,
                ]);
            }

        });
    }
}
