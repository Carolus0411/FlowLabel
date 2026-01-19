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
        Schema::create('label_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->index(); // Order ID from PDF
            $table->string('original_filename'); // Original PDF filename
            $table->string('split_filename'); // Split PDF filename
            $table->integer('page_number'); // Page number in original PDF
            $table->json('extracted_data')->nullable(); // Extracted data from PDF
            $table->string('order_type')->default('sales_order'); // sales_order, purchase_order, delivery_order
            $table->string('status')->default('processed'); // processed, failed, pending
            $table->text('raw_text')->nullable(); // Raw extracted text
            $table->string('file_path'); // Path to the split PDF file
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['order_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_orders');
    }
};
