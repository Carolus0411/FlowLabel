<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_settlement_detail', function (Blueprint $table) {
            $table->id();
            $table->string('sales_settlement_code')->index();
            $table->string('sales_invoice_code')->index();
            $table->foreignId('currency_id')->index()->default(0);
            $table->decimal('currency_rate', 12, 2)->default(0);
            $table->decimal('invoice_balance_amount', 12, 2)->default(0);
            $table->decimal('foreign_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['open','close','void'])->index()->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_settlement_detail');
    }
};
