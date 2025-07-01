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
        Schema::create('sales_settlement_prepaid', function (Blueprint $table) {
            $table->id();
            $table->string('source_type')->index();
            $table->string('source_id')->index();
            $table->foreignId('currency_id')->index()->default(0);
            $table->decimal('currency_rate', 12, 2)->default(0);
            $table->decimal('foreign_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_settlement_prepaid');
    }
};
