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
        Schema::create('inventory_ledgers', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('service_charge_id')->index();
            $table->decimal('qty', 12, 2);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('type')->index(); // 'in', 'out'
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        Schema::table('item_type', function (Blueprint $table) {
            $table->boolean('is_stock')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_ledgers');
        Schema::table('item_type', function (Blueprint $table) {
            $table->dropColumn('is_stock');
        });
    }
};
