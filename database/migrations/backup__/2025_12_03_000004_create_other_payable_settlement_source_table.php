<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_payable_settlement_source', function (Blueprint $table) {
            $table->id();
            $table->string('other_payable_settlement_code')->index('opss_settlement_code_idx');
            $table->string('payment_method')->index('opss_payment_method_idx');
            $table->string('settleable_type')->index('opss_settleable_type_idx');
            $table->string('settleable_id')->index('opss_settleable_id_idx');
            $table->foreignId('currency_id')->index('opss_currency_idx')->default(0);
            $table->decimal('currency_rate', 12, 2)->default(0);
            $table->decimal('foreign_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_payable_settlement_source');
    }
};
