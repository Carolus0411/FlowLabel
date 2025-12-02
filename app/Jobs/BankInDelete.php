<?php

namespace App\Jobs;

use App\Models\BankIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BankInDelete implements ShouldQueue
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

            $this->bankIn->details()->delete();
            $this->bankIn->delete();

        });
    }
}
