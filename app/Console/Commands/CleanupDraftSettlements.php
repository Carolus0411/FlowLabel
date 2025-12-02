<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesSettlement;
use App\Models\PurchaseSettlement;

class CleanupDraftSettlements extends Command
{
    protected $signature = 'settlement:cleanup-drafts {--hours=24 : Hours since last update to consider stale}';
    protected $description = 'Clean up draft (unsaved) settlements and restore invoice balances';

    public function handle()
    {
        $hours = $this->option('hours');
        $cutoffTime = now()->subHours($hours);

        $this->info("Cleaning up draft settlements older than {$hours} hours (before {$cutoffTime})...");

        // Clean Sales Settlements
        $salesCount = $this->cleanupSalesSettlements($cutoffTime);

        // Clean Purchase Settlements
        $purchaseCount = $this->cleanupPurchaseSettlements($cutoffTime);

        $this->info("Cleanup complete: {$salesCount} sales settlements, {$purchaseCount} purchase settlements removed.");

        return Command::SUCCESS;
    }

    protected function cleanupSalesSettlements($cutoffTime): int
    {
        $drafts = SalesSettlement::with(['sources', 'details'])
            ->where('saved', '0')
            ->where('updated_at', '<', $cutoffTime)
            ->get();

        $count = 0;
        foreach ($drafts as $settlement) {
            // Release details - restore balance_amount
            foreach ($settlement->details as $detail) {
                try {
                    $invoice = \App\Models\SalesInvoice::where('code', $detail->sales_invoice_code)->first();
                    if ($invoice) {
                        $invoice->increment('balance_amount', $detail->foreign_amount);
                        $invoice->recalcPaymentStatus();
                    }
                } catch (\Throwable $ex) {
                    $this->warn("Could not restore invoice balance for {$detail->sales_invoice_code}: {$ex->getMessage()}");
                }
            }

            // Delete sources and details
            $settlement->sources()->delete();
            $settlement->details()->delete();

            // Delete without triggering model events (no auth user in console)
            \DB::table('sales_settlement')->where('id', $settlement->id)->delete();

            $count++;
            $this->line("Deleted draft Sales Settlement ID: {$settlement->id}");
        }

        return $count;
    }

    protected function cleanupPurchaseSettlements($cutoffTime): int
    {
        $drafts = PurchaseSettlement::with(['sources', 'details'])
            ->where('saved', '0')
            ->where('updated_at', '<', $cutoffTime)
            ->get();

        $count = 0;
        foreach ($drafts as $settlement) {
            // Release details - restore balance_amount
            foreach ($settlement->details as $detail) {
                try {
                    $invoice = \App\Models\PurchaseInvoice::where('code', $detail->purchase_invoice_code)->first();
                    if ($invoice) {
                        $invoice->increment('balance_amount', $detail->foreign_amount);
                        $invoice->recalcPaymentStatus();
                    }
                } catch (\Throwable $ex) {
                    $this->warn("Could not restore invoice balance for {$detail->purchase_invoice_code}: {$ex->getMessage()}");
                }
            }

            // Delete sources and details
            $settlement->sources()->delete();
            $settlement->details()->delete();

            // Delete without triggering model events (no auth user in console)
            \DB::table('purchase_settlement')->where('id', $settlement->id)->delete();

            $count++;
            $this->line("Deleted draft Purchase Settlement ID: {$settlement->id}");
        }

        return $count;
    }
}
