<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal', function (Blueprint $table) {
            if (!Schema::hasColumn('journal', 'supplier_id')) {
                $table->foreignId('supplier_id')->index()->nullable()->after('contact_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal', function (Blueprint $table) {
            if (Schema::hasColumn('journal', 'supplier_id')) {
                $table->dropForeignIdFor(\App\Models\Supplier::class);
                $table->dropColumn('supplier_id');
            }
        });
    }
};
