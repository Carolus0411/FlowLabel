<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_payable_invoice_detail', function (Blueprint $table) {
            $table->text('note')->nullable()->after('service_charge_id');
        });
    }

    public function down(): void
    {
        Schema::table('other_payable_invoice_detail', function (Blueprint $table) {
            if (Schema::hasColumn('other_payable_invoice_detail', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
