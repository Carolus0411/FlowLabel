<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receival', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('receival_date')->nullable();
            $table->foreignId('purchase_order_id')->index()->nullable();
            $table->string('purchase_order_code')->index()->nullable();
            $table->foreignId('supplier_id')->index()->nullable();
            $table->string('transport')->index()->nullable();
            $table->string('service_type')->index()->nullable();
            $table->string('note')->nullable();
            $table->decimal('total_qty', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->integer('saved')->index()->default(0);
            $table->enum('status', ['open','close','void'])->index()->default('open');
            $table->foreignId('created_by')->index()->default(0);
            $table->foreignId('updated_by')->index()->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receival');
    }
};
