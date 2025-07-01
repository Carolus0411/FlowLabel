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
        Schema::create('sales_settlement', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('source_type')->index();
            $table->string('source_account_id')->index();
            $table->string('source_id')->index();
            $table->date('date')->nullable();
            $table->foreignId('contact_id')->index()->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->integer('saved')->index()->default(0);
            $table->enum('status', ['open','close','void'])->index()->default('open');
            $table->foreignId('created_by')->index()->default(0);
            $table->foreignId('updated_by')->index()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_settlement');
    }
};
