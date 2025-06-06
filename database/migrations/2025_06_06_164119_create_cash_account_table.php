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
        Schema::create('cash_account', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('currency_id')->index()->nullable();
            $table->string('coa_code',20)->index();
            $table->boolean('is_active')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_account');
    }
};
