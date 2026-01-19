<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_charge', function (Blueprint $table) {
            $table->unsignedBigInteger('service_charge_group_id')->nullable()->after('transport');
            $table->foreign('service_charge_group_id')->references('id')->on('service_charge_group')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('service_charge', function (Blueprint $table) {
            $table->dropForeign(['service_charge_group_id']);
            $table->dropColumn('service_charge_group_id');
        });
    }
};
