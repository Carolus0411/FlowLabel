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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->foreignId('category_id')->index()->default(0);
            $table->foreignId('brand_id')->index()->default(0);
            $table->text('features')->nullable();
            $table->text('description')->nullable();
            $table->string('dimension')->nullable();
            $table->decimal('weight', 10, 2)->default(0);
            $table->text('meta_description')->nullable();
            $table->text('meta_keyword')->nullable();
            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
            $table->string('image3')->nullable();
            $table->string('image4')->nullable();
            $table->string('image5')->nullable();
            $table->boolean('is_featured')->index();
            $table->boolean('is_new')->index();
            $table->boolean('is_active')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
