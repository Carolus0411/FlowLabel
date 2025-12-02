<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('telephone')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('npwp')->nullable();
            $table->text('information')->nullable();
            $table->integer('term_of_payment')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier');
    }
};
