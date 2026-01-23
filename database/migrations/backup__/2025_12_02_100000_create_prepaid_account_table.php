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
        Schema::create('prepaid_account', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->date('date');
            $table->string('coa_code')->index(); // 204-001, 204-002
            $table->string('source_type')->index(); // CashIn, CashOut, BankIn, BankOut
            $table->string('source_code')->index(); // Reference to source code
            $table->foreignId('contact_id')->nullable()->index(); // For CashIn, BankIn
            $table->foreignId('supplier_id')->nullable()->index(); // For CashOut, BankOut
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prepaid_account');
    }
};
