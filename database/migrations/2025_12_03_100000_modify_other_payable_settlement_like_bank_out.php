<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_payable_settlement', function (Blueprint $table) {
            // Add bank_account_id like bank_out
            if (!Schema::hasColumn('other_payable_settlement', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->index()->nullable()->after('date');
            }
            // Add total_amount like bank_out
            if (!Schema::hasColumn('other_payable_settlement', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('supplier_id');
            }
            // Add has_payable like bank_out
            if (!Schema::hasColumn('other_payable_settlement', 'has_payable')) {
                $table->integer('has_payable')->default(0)->after('status');
            }
        });

        // Modify other_payable_settlement_detail to match bank_out_detail structure
        Schema::table('other_payable_settlement_detail', function (Blueprint $table) {
            // Add other_payable_settlement_id if not exists
            if (!Schema::hasColumn('other_payable_settlement_detail', 'other_payable_settlement_id')) {
                $table->foreignId('other_payable_settlement_id')->index('opsd_settlement_id_idx')->default(0)->after('id');
            }
            // Add coa_code like bank_out_detail
            if (!Schema::hasColumn('other_payable_settlement_detail', 'coa_code')) {
                $table->string('coa_code', 20)->index('opsd_coa_code_idx')->nullable()->after('other_payable_settlement_id');
            }
            // Add note like bank_out_detail
            if (!Schema::hasColumn('other_payable_settlement_detail', 'note')) {
                $table->string('note')->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('other_payable_settlement', function (Blueprint $table) {
            $table->dropColumn(['bank_account_id', 'total_amount', 'has_payable']);
        });

        Schema::table('other_payable_settlement_detail', function (Blueprint $table) {
            $table->dropColumn(['other_payable_settlement_id', 'coa_code', 'note']);
        });
    }
};
