<?php

namespace App\Jobs;

use App\Models\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequestApprove implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Request $request,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $this->request->update([
                'status' => 'approved',
            ]);

            if ($this->request->type == 'void')
            {
                if ($this->request->requestable_type == 'App\Models\CashIn') {;
                    \App\Jobs\CashInVoid::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\CashOut') {;
                    \App\Jobs\CashOutVoid::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\SalesInvoice') {;
                    \App\Jobs\SalesInvoiceVoid::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\SalesSettlement') {;
                    \App\Jobs\SalesSettlementVoid::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\Journal') {;
                    \App\Jobs\Journal::dispatchSync($this->request->requestable);
                }
            }

            if ($this->request->type == 'delete')
            {
                if ($this->request->requestable_type == 'App\Models\CashIn') {;
                    \App\Jobs\CashInDelete::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\CashOut') {;
                    \App\Jobs\CashOutDelete::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\SalesInvoice') {;
                    \App\Jobs\SalesInvoiceDelete::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\SalesSettlement') {;
                    \App\Jobs\SalesSettlementDelete::dispatchSync($this->request->requestable);
                }
                if ($this->request->requestable_type == 'App\Models\Journal') {;
                    \App\Jobs\JournalDelete::dispatchSync($this->request->requestable);
                }
            }
        });
    }
}
