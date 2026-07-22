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
        Schema::create('data_ocr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocr_template_id')->constrained('ocr_templates')->onDelete('cascade');
            $table->json('extracted_fields');
            $table->string('nama_file');
            $table->text('content');
            $table->string('file_path');
            $table->enum('status', ['Uploaded', 'Failed', 'Rejected', 'Approved'])->default('Uploaded');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_ocr');
    }
};
