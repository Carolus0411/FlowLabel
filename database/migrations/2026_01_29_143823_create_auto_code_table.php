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
        Schema::create('auto_code', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('key')->unique()->nullable();
            $blueprint->string('format')->nullable();
            $blueprint->string('prefix')->unique()->nullable();
            $blueprint->integer('num')->default(0);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_code');
    }
};
