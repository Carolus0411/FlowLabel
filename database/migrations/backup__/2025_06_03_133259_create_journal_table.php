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
        Schema::create('journal', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->date('date')->index();
            $table->string('note')->nullable();
            $table->string('type')->index()->nullable();
            $table->decimal('debit_total', 12, 2)->default(0);
            $table->decimal('credit_total', 12, 2)->default(0);
            $table->foreignId('contact_id')->index()->nullable()->default(0);
            $table->string('ref_name')->index()->nullable();
            $table->string('ref_id')->index()->nullable();
            $table->enum('status', ['open','close','void'])->index()->default('open');
            $table->integer('saved')->index()->default(0);
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
        Schema::dropIfExists('journal');
    }
};
