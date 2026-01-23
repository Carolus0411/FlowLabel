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
        Schema::create('delivery_order_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->index();
            $table->foreignId('sales_order_detail_id')->index()->nullable();
            $table->foreignId('service_charge_id')->index();
            $table->foreignId('uom_id')->index();
            $table->decimal('order_qty', 12, 2)->default(0);
            $table->decimal('delivered_qty', 12, 2)->default(0);
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_detail');
    }
};
