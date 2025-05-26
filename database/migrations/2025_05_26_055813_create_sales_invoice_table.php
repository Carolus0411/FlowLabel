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
        Schema::create('sales_invoice', function (Blueprint $table) {
            $table->id();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('contact_id')->index()->default(0);
            $table->integer('top')->default(0);
            $table->decimal('dpp', 12, 2)->default(0);
            $table->foreignId('ppn_id')->index()->default(0);
            $table->decimal('ppn_amount', 10, 2)->default(0);
            $table->foreignId('pph_id')->index()->default(0);
            $table->decimal('pph_amount', 10, 2)->default(0);
            $table->decimal('stamp', 10, 2)->default(0);
            $table->decimal('invoice_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice');
    }
};
