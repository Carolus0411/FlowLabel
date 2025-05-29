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
            $table->string('code')->unique();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('contact_id')->index()->default(0);
            $table->integer('top')->default(0);
            $table->decimal('dpp_amount', 12, 2)->default(0);
            $table->foreignId('ppn_id')->index()->default(0);
            $table->decimal('ppn_amount', 10, 2)->default(0);
            $table->foreignId('pph_id')->index()->default(0);
            $table->decimal('pph_amount', 10, 2)->default(0);
            $table->decimal('stamp_amount', 10, 2)->default(0);
            $table->decimal('invoice_amount', 12, 2)->default(0);
            $table->integer('saved')->index()->default(0);
            $table->enum('status', ['open','close','void'])->index()->default('open');
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
