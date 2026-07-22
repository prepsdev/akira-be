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
        // Insert app branding settings
        DB::table('global_settings')->insert([
            [
                'setting_name' => 'app_name',
                'description' => 'Application name displayed in login and sidebar',
                'is_enabled' => true,
                'setting_value' => 'OCR AI Platform',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_name' => 'app_description',
                'description' => 'Application description/tagline',
                'is_enabled' => true,
                'setting_value' => 'DOCUMENT INTELLIGENCE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_name' => 'app_logo',
                'description' => 'Application logo image path (PNG/JPG)',
                'is_enabled' => true,
                'setting_value' => null,
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
        DB::table('global_settings')
            ->whereIn('setting_name', ['app_name', 'app_description', 'app_logo'])
            ->delete();
    }
};
