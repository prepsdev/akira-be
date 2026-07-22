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
        Schema::create('naming_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocr_template_id')->constrained('ocr_templates')->onDelete('cascade');
            $table->string('naming_pattern');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('naming_rules');
    }
};
