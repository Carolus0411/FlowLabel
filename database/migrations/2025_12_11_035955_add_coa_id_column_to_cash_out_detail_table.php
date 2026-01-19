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
        Schema::table('cash_out_detail', function (Blueprint $table) {
            $table->foreignId('coa_id')->index()->nullable()->after('cash_out_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_out_detail', function (Blueprint $table) {
            $table->dropColumn('coa_id');
        });
    }
};
