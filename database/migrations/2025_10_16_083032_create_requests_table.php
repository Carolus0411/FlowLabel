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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->index();
            $table->string('requestable_id')->index();
            $table->string('requestable_type')->index();
            $table->text('description')->nullable();
            $table->text('response')->nullable();
            $table->enum('status', ['open','approved','rejected'])->index()->default('open');
            $table->foreignId('created_by')->index()->default(0);
            $table->foreignId('updated_by')->index()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
