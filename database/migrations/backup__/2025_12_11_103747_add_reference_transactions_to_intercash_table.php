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
        Schema::table('intercash', function (Blueprint $table) {
            // Reference to created transactions
            $table->unsignedBigInteger('cash_out_id')->nullable()->after('posted_at');
            $table->unsignedBigInteger('bank_out_id')->nullable()->after('cash_out_id');
            $table->unsignedBigInteger('cash_in_id')->nullable()->after('bank_out_id');
            $table->unsignedBigInteger('bank_in_id')->nullable()->after('cash_in_id');

            // Foreign keys
            $table->foreign('cash_out_id')->references('id')->on('cash_out')->onDelete('set null');
            $table->foreign('bank_out_id')->references('id')->on('bank_out')->onDelete('set null');
            $table->foreign('cash_in_id')->references('id')->on('cash_in')->onDelete('set null');
            $table->foreign('bank_in_id')->references('id')->on('bank_in')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intercash', function (Blueprint $table) {
            $table->dropForeign(['cash_out_id']);
            $table->dropForeign(['bank_out_id']);
            $table->dropForeign(['cash_in_id']);
            $table->dropForeign(['bank_in_id']);

            $table->dropColumn(['cash_out_id', 'bank_out_id', 'cash_in_id', 'bank_in_id']);
        });
    }
};
