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
        Schema::create('intercash', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->date('date');
            $table->string('description')->nullable();
            $table->enum('type', ['cash-to-cash', 'cash-to-bank', 'bank-to-cash', 'bank-to-bank'])->default('cash-to-cash');

            // From Account
            $table->unsignedBigInteger('from_cash_account_id')->nullable();
            $table->unsignedBigInteger('from_bank_account_id')->nullable();
            $table->string('from_account_code')->nullable();

            // To Account
            $table->unsignedBigInteger('to_cash_account_id')->nullable();
            $table->unsignedBigInteger('to_bank_account_id')->nullable();
            $table->string('to_account_code')->nullable();

            // Transaction Codes
            $table->string('no_code_from')->nullable();
            $table->string('no_code_to')->nullable();

            // Amount
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('currency_rate', 18, 2)->default(1);
            $table->decimal('foreign_amount', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);

            // Status & Approvals
            $table->boolean('saved')->default(0);
            $table->enum('status', ['open', 'approve', 'post', 'void'])->default('open');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->dateTime('posted_at')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('from_cash_account_id')->references('id')->on('cash_account')->onDelete('set null');
            $table->foreign('to_cash_account_id')->references('id')->on('cash_account')->onDelete('set null');
            $table->foreign('from_bank_account_id')->references('id')->on('bank_account')->onDelete('set null');
            $table->foreign('to_bank_account_id')->references('id')->on('bank_account')->onDelete('set null');
            $table->foreign('currency_id')->references('id')->on('currency')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intercash');
    }
};
