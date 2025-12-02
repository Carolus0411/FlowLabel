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
        Schema::table('bank_in', function (Blueprint $table) {
            $table->integer('has_receivable')->index()->default(0)->after('has_prepaid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_in', function (Blueprint $table) {
            $table->dropColumn('has_receivable');
        });
    }
};
