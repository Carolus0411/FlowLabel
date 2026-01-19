<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_payable_invoice', function (Blueprint $table) {
            // Drop indexes first for SQLite compatibility
            if (Schema::hasColumn('other_payable_invoice', 'transport')) {
                $table->dropIndex('other_payable_invoice_transport_index');
            }
            if (Schema::hasColumn('other_payable_invoice', 'service_type')) {
                $table->dropIndex('other_payable_invoice_service_type_index');
            }
        });

        Schema::table('other_payable_invoice', function (Blueprint $table) {
            $table->dropColumn(['transport', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::table('other_payable_invoice', function (Blueprint $table) {
            $table->string('transport')->index()->nullable()->after('due_date');
            $table->string('service_type')->index()->nullable()->after('transport');
        });
    }
};
