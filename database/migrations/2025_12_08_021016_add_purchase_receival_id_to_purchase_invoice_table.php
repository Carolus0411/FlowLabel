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
        Schema::table('purchase_invoice', function (Blueprint $table) {
            $table->foreignId('purchase_receival_id')->nullable()->index()->after('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice', function (Blueprint $table) {
            $table->dropColumn('purchase_receival_id');
        });
    }
};
