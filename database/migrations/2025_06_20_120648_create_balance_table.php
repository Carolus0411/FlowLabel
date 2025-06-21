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
        Schema::create('balance', function (Blueprint $table) {
            $table->id();
            $table->year('year')->index();
            $table->string('coa_code', 20)->index();
            $table->string('dc', 20);
            $table->decimal('debit', 18, 2);
            $table->decimal('credit', 18, 2);
            $table->decimal('amount', 18, 2);
            $table->timestamps();
            $table->unique(['year', 'coa_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance');
    }
};
