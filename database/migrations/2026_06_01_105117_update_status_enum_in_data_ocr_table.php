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
        Schema::table('data_ocr', function (Blueprint $table) {
            // Change status enum to include 'Processing'
            \DB::statement("ALTER TABLE data_ocr MODIFY COLUMN status ENUM('Processing', 'Uploaded', 'Failed', 'Rejected', 'Approved') DEFAULT 'Processing'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_ocr', function (Blueprint $table) {
            // Revert back to original enum
            \DB::statement("ALTER TABLE data_ocr MODIFY COLUMN status ENUM('Uploaded', 'Failed', 'Rejected', 'Approved') DEFAULT 'Uploaded'");
        });
    }
};
