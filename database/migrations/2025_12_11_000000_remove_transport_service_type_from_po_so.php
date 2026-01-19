<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->dropIndex('purchase_order_transport_index');
        });
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->dropIndex('purchase_order_service_type_index');
        });
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->dropColumn(['transport', 'service_type']);
        });

        Schema::table('sales_order', function (Blueprint $table) {
            $table->dropIndex('sales_order_transport_index');
        });
        Schema::table('sales_order', function (Blueprint $table) {
            $table->dropIndex('sales_order_service_type_index');
        });
        Schema::table('sales_order', function (Blueprint $table) {
            $table->dropColumn(['transport', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->string('transport')->index()->nullable();
            $table->string('service_type')->index()->nullable();
        });

        Schema::table('sales_order', function (Blueprint $table) {
            $table->string('transport')->index()->nullable();
            $table->string('service_type')->index()->nullable();
        });
    }
};
