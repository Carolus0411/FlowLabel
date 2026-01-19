<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\OtherPayableInvoice;
use App\Models\OtherPayableInvoiceDetail;
use App\Models\OtherPayableSettlement;
use App\Models\OtherPayableSettlementDetail;
use App\Models\OtherPayableSettlementSource;

class TruncateOtherPayablesAndJournal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --force to skip interactive confirmation
     */
    protected $signature = 'truncate:other-payables-journals {--force : Force truncate without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Deletes Other Payable invoices/settlements and related journals safely. This can be destructive. Use --force to skip prompts.';

    public function handle(): int
    {
        $force = $this->option('force');

        // Protect production environment unless forced
        if (config('app.env') === 'production' && !$force) {
            $this->error('This command is dangerous in production. Use --force to override.');
            return Command::FAILURE;
        }

        // Calculate counts for display to the user
        $refNames = ['OtherPayableInvoice', 'OtherPayableSettlement'];
        $journalCount = Journal::whereIn('ref_name', $refNames)->count();
        $journalDetailCount = 0;
        if ($journalCount > 0) {
            $journalCodes = Journal::whereIn('ref_name', $refNames)->pluck('code')->toArray();
            $journalDetailCount = \App\Models\JournalDetail::whereIn('code', $journalCodes)->count();
        }

        $invoiceCount = OtherPayableInvoice::count();
        $invoiceDetailCount = OtherPayableInvoiceDetail::count();
        $settlementCount = OtherPayableSettlement::count();
        $settlementDetailCount = OtherPayableSettlementDetail::count();
        $settlementSourceCount = class_exists(OtherPayableSettlementSource::class) ? OtherPayableSettlementSource::count() : 0;

        $this->info("This operation will delete the following records:");
        $this->info("  - Journals: {$journalCount}");
        $this->info("  - Journal details: {$journalDetailCount}");
        $this->info("  - Other Payable Invoices: {$invoiceCount}");
        $this->info("  - Other Payable Invoice Details: {$invoiceDetailCount}");
        $this->info("  - Other Payable Settlements: {$settlementCount}");
        $this->info("  - Other Payable Settlement Details: {$settlementDetailCount}");
        if ($settlementSourceCount > 0) {
            $this->info("  - Other Payable Settlement Sources: {$settlementSourceCount}");
        }

        if (!$force && !$this->confirm('Are you sure you want to continue? This action is irreversible.')) {
            $this->info('Aborted. No changes made.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            Schema::disableForeignKeyConstraints();

            \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($refNames) {

            // Find journals related to the modules
            $refNames = ['OtherPayableInvoice', 'OtherPayableSettlement'];
            $journalCodes = Journal::whereIn('ref_name', $refNames)->pluck('code')->toArray();

                if (!empty($journalCodes)) {
                    $this->info('Deleting journal details for ' . count($journalCodes) . ' journals...');
                    JournalDetail::whereIn('code', $journalCodes)->delete();

                    $this->info('Deleting journal headers...');
                    Journal::whereIn('ref_name', $refNames)->delete();
                } else {
                    $this->info('No journals found for specified module refs.');
                }

            // Delete settlement-related records (child first)
                $this->info('Deleting other payable settlement details...');
                OtherPayableSettlementDetail::truncate();

            $this->info('Deleting other payable settlement sources...');
                if (class_exists(OtherPayableSettlementSource::class)) {
                    OtherPayableSettlementSource::truncate();
                }

                $this->info('Deleting other payable settlements...');
                OtherPayableSettlement::truncate();

            // Delete invoice-related records (child first)
                $this->info('Deleting other payable invoice details...');
                OtherPayableInvoiceDetail::truncate();

                $this->info('Deleting other payable invoices...');
                OtherPayableInvoice::truncate();
            });

            Schema::enableForeignKeyConstraints();
            DB::commit();

            $this->info('Other Payables and related journals truncated successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            Schema::enableForeignKeyConstraints();
            $this->error('Failed to truncate records: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
