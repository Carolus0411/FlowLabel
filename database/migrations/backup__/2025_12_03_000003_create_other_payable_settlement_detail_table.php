<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_payable_settlement_detail', function (Blueprint $table) {
            $table->id();
            $table->string('other_payable_settlement_code')->index('opsd_settlement_code_idx');
            $table->string('other_payable_invoice_code')->index('opsd_invoice_code_idx');
            $table->foreignId('currency_id')->index('opsd_currency_idx')->default(0);
            $table->decimal('currency_rate', 12, 2)->default(0);
            $table->decimal('invoice_balance_amount', 12, 2)->default(0);
            $table->decimal('foreign_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['open','close','void'])->index('opsd_status_idx')->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_payable_settlement_detail');
    }
};
