<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_payable_invoice', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('transport')->index()->nullable();
            $table->string('service_type')->index()->nullable();
            $table->string('invoice_type')->index()->nullable();
            $table->string('note')->nullable();
            $table->foreignId('supplier_id')->index()->nullable();
            $table->integer('top')->default(0);
            $table->decimal('dpp_amount', 12, 2)->default(0);
            $table->foreignId('ppn_id')->index()->nullable();
            $table->decimal('ppn_amount', 10, 2)->default(0);
            $table->foreignId('pph_id')->index()->nullable();
            $table->decimal('pph_amount', 10, 2)->default(0);
            $table->decimal('stamp_amount', 10, 2)->default(0);
            $table->decimal('invoice_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->integer('saved')->index()->default(0);
            $table->enum('status', ['open','close','void'])->index()->default('open');
            $table->enum('payment_status', ['unpaid','paid','outstanding'])->index()->default('unpaid');
            $table->foreignId('created_by')->index()->default(0);
            $table->foreignId('updated_by')->index()->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_payable_invoice');
    }
};
