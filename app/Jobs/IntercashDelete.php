<?php

namespace App\Jobs;

use App\Models\Intercash;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class IntercashDelete implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Intercash $intercash,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Delete related transactions if they exist
        if ($this->intercash->cash_out_id) {
            $this->intercash->cashOut?->delete();
        }

        if ($this->intercash->bank_out_id) {
            $this->intercash->bankOut?->delete();
        }

        if ($this->intercash->cash_in_id) {
            $this->intercash->cashIn?->delete();
        }

        if ($this->intercash->bank_in_id) {
            $this->intercash->bankIn?->delete();
        }

        // Finally delete the intercash record
        $this->intercash->delete();
    }
}
