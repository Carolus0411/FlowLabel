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
        Schema::table('cash_out', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_out', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('contact_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_out', function (Blueprint $table) {
            if (Schema::hasColumn('cash_out', 'supplier_id')) {
                $table->dropColumn('supplier_id');
            }
        });
    }
};
