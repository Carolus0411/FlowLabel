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
        Schema::table('order_label', function (Blueprint $table) {
            $table->foreignId('three_pl_id')->nullable()->after('batch_no')->constrained('three_pls')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_label', function (Blueprint $table) {
            $table->dropForeign(['three_pl_id']);
            $table->dropColumn('three_pl_id');
        });
    }
};
