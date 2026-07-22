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
            // Update status enum to include 'Queued'
            \DB::statement("ALTER TABLE data_ocr MODIFY COLUMN status ENUM('Queued', 'Processing', 'Uploaded', 'Failed', 'Rejected', 'Approved') DEFAULT 'Queued'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_ocr', function (Blueprint $table) {
            // Revert back to previous enum
            \DB::statement("ALTER TABLE data_ocr MODIFY COLUMN status ENUM('Processing', 'Uploaded', 'Failed', 'Rejected', 'Approved') DEFAULT 'Processing'");
        });
    }
};
