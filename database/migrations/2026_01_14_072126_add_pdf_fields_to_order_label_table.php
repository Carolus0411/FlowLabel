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
        Schema::table('order_label', function (Blueprint $table) {
            $table->string('original_filename')->nullable()->after('note');
            $table->string('split_filename')->nullable()->after('original_filename');
            $table->integer('page_number')->nullable()->after('split_filename');
            $table->string('file_path')->nullable()->after('page_number');
            $table->longText('extracted_text')->nullable()->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_label', function (Blueprint $table) {
            $table->dropColumn(['original_filename', 'split_filename', 'page_number', 'file_path', 'extracted_text']);
        });
    }
};
