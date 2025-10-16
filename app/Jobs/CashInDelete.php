<?php

namespace App\Jobs;

use App\Models\CashIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CashInDelete implements ShouldQueue
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

            $this->cashIn->details()->delete();
            $this->cashIn->delete();

        });
    }
}
