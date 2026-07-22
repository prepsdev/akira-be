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
        Schema::create('email_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('frequency');
            $table->unsignedBigInteger('ocr_template_id')->nullable();
            $table->string('export_format')->default('xlsx');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('ocr_template_id')->references('id')->on('ocr_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_schedules');
    }
};
