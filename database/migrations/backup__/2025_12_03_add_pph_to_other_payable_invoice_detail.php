<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_payable_invoice_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('other_payable_invoice_detail', 'pph_id')) {
                $table->foreignId('pph_id')->index()->nullable()->after('currency_id');
            }
            if (!Schema::hasColumn('other_payable_invoice_detail', 'pph_amount')) {
                $table->decimal('pph_amount', 12, 2)->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('other_payable_invoice_detail', function (Blueprint $table) {
            if (Schema::hasColumn('other_payable_invoice_detail', 'pph_id')) {
                $table->dropColumn('pph_id');
            }
            if (Schema::hasColumn('other_payable_invoice_detail', 'pph_amount')) {
                $table->dropColumn('pph_amount');
            }
        });
    }
};
