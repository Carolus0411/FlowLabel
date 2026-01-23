<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_settlement_detail', function (Blueprint $table) {
            $table->string('purchase_order_code')->nullable()->index()->after('purchase_invoice_code');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_settlement_detail', function (Blueprint $table) {
            $table->dropColumn('purchase_order_code');
        });
    }
};
