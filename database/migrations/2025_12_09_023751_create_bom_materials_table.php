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
        Schema::create('bom_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('service_charge')->onDelete('cascade'); // Raw Material
            $table->decimal('required_qty', 15, 2); // Total qty needed
            $table->foreignId('uom_id')->nullable()->constrained('uom')->onDelete('set null');
            $table->decimal('available_qty', 15, 2)->default(0); // Current stock
            $table->boolean('is_sufficient')->default(false); // Stock sufficient or not
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_materials');
    }
};
