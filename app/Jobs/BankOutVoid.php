<?php

namespace App\Jobs;

use App\Models\BankOut;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BankOutVoid implements ShouldQueue
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

            $this->bankOut->update([
                'status' => 'void'
            ]);

            $journal = Journal::query()
                ->where('ref_name', class_basename($this->bankOut))
                ->where('ref_id', $this->bankOut->code)
                ->first();

            if ($journal) {
                $journal->update([
                    'status' => 'void'
                ]);
            }

        });
    }
}
