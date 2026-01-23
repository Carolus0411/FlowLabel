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
        Schema::table('bank_out', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_out', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('contact_id')->index();
            }
            if (!Schema::hasColumn('bank_out', 'has_payable')) {
                $table->integer('has_payable')->default(0)->after('saved')->index();
            }
            if (!Schema::hasColumn('bank_out', 'used_payable')) {
                $table->integer('used_payable')->default(0)->after('has_payable')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_out', function (Blueprint $table) {
            if (Schema::hasColumn('bank_out', 'supplier_id')) {
                $table->dropColumn('supplier_id');
            }
            if (Schema::hasColumn('bank_out', 'has_payable')) {
                $table->dropColumn('has_payable');
            }
            if (Schema::hasColumn('bank_out', 'used_payable')) {
                $table->dropColumn('used_payable');
            }
        });
    }
};
