<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_name')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('global_settings')->insert([
            [
                'setting_name' => 'field_suggestions',
                'is_enabled' => true,
                'description' => 'Enable AI-powered field suggestions from approved documents',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_settings');
    }
};
