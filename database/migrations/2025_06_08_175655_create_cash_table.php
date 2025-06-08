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
        Schema::create('cash', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['in','out'])->index()->default('in');
            $table->string('code')->unique();
            $table->date('date')->nullable();
            $table->foreignId('cash_account_id')->index()->nullable();
            $table->foreignId('contact_id')->index()->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash');
    }
};
