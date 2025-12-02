<?php

namespace App\Jobs;

use App\Models\BankOut;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BankOutDelete implements ShouldQueue
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

            $this->bankOut->details()->delete();
            $this->bankOut->delete();

        });
    }
}
