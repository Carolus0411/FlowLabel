<?php

namespace App\Jobs;

use App\Models\BankIn;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BankInVoid implements ShouldQueue
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

            $this->bankIn->update([
                'status' => 'void'
            ]);

            $journal = Journal::query()
                ->where('ref_name', class_basename($this->bankIn))
                ->where('ref_id', $this->bankIn->code)
                ->first();

            if ($journal) {
                $journal->update([
                    'status' => 'void'
                ]);
            }

        });
    }
}
