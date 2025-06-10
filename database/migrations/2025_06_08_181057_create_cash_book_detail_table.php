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
        Schema::create('cash_book_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_book_id')->index()->default(0);
            $table->string('coa_code',20)->index();
            $table->foreignId('currency_id')->index()->default(0);
            $table->decimal('currency_rate', 12, 2)->default(0);
            $table->decimal('foreign_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_book_detail');
    }
};
