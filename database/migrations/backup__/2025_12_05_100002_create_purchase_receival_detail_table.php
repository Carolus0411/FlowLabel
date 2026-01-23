<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receival_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receival_id')->index();
            $table->foreignId('purchase_order_detail_id')->index()->nullable();
            $table->foreignId('service_charge_id')->index();
            $table->foreignId('uom_id')->index();
            $table->decimal('order_qty', 12, 2)->default(0);
            $table->decimal('received_qty', 12, 2)->default(0);
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receival_detail');
    }
};
