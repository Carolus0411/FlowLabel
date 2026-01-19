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
            $table->timestamp('printed_at')->nullable()->after('updated_by');
            $table->foreignId('printed_by')->nullable()->after('printed_at');
            $table->integer('print_count')->default(0)->after('printed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_label', function (Blueprint $table) {
            $table->dropColumn(['printed_at', 'printed_by', 'print_count']);
        });
    }
};
