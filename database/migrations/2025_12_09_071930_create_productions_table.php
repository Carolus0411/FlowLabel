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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');
            $table->foreignId('bom_id')->constrained('boms');
            $table->foreignId('product_id')->constrained('service_charge');
            $table->decimal('qty', 15, 2);
            $table->foreignId('uom_id')->nullable()->constrained('uom');
            $table->string('status')->default('draft'); // draft, done
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('production_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('service_charge');
            $table->decimal('qty', 15, 2);
            $table->foreignId('uom_id')->nullable()->constrained('uom');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_details');
        Schema::dropIfExists('productions');
    }
};
