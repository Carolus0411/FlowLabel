<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesInvoice;

class RecalcInvoicePaymentStatus extends Command
{
    protected $signature = 'recalc:invoice-status';
    protected $description = 'Recalculate payment_status for all sales invoices';

    public function handle()
    {
        $this->info('Recalculating payment_status for SalesInvoice...');
        $count = 0;
        foreach (SalesInvoice::query()->cursor() as $invoice) {
            $invoice->recalcPaymentStatus();
            $count++;
        }
        $this->info('Recalculated '.$count.' invoices.');
        return 0;
    }
}
