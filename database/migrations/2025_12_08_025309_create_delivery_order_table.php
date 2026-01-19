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
        Schema::create('delivery_order', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('delivery_date')->nullable();
            $table->foreignId('sales_order_id')->index()->nullable();
            $table->string('sales_order_code')->index()->nullable();
            $table->foreignId('contact_id')->index()->nullable();
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
        Schema::dropIfExists('delivery_order');
    }
};
