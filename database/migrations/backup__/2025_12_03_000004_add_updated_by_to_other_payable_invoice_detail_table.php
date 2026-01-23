<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_payable_invoice_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('other_payable_invoice_detail', 'updated_by')) {
                $table->integer('updated_by')->index()->default(0)->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('other_payable_invoice_detail', function (Blueprint $table) {
            if (Schema::hasColumn('other_payable_invoice_detail', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
