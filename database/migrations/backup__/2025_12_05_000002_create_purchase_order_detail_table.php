<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->index();
            $table->foreignId('service_charge_id')->index();
            $table->foreignId('uom_id')->index();
            $table->foreignId('currency_id')->index();
            $table->decimal('currency_rate', 12, 2)->default(1);
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('foreign_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_detail');
    }
};
