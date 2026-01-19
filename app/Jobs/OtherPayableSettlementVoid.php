<?php

namespace App\Jobs;

use App\Models\OtherPayableSettlement;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtherPayableSettlementVoid implements ShouldQueue
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

            $this->otherPayableSettlement->update([
                'status' => 'void'
            ]);

            $journal = Journal::query()
                ->where('ref_name', class_basename($this->otherPayableSettlement))
                ->where('ref_id', $this->otherPayableSettlement->code)
                ->first();

            if ($journal) {
                $journal->update([
                    'status' => 'void'
                ]);
            }

        });
    }
}
