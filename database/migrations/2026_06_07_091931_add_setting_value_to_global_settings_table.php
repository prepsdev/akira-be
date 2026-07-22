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
        Schema::table('global_settings', function (Blueprint $table) {
            $table->text('setting_value')->nullable()->after('is_enabled');
        });

        DB::table('global_settings')->insert([
            [
                'setting_name' => 'auto_upload_watch_directory',
                'is_enabled' => false,
                'setting_value' => 'C:\\OCR_AutoUpload\\free',
                'description' => 'Directory path to monitor for auto-upload files',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_name' => 'auto_upload_success_directory',
                'is_enabled' => true,
                'setting_value' => 'C:\\OCR_AutoUpload\\uploaded',
                'description' => 'Directory to move successfully uploaded files',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_name' => 'auto_upload_failed_directory',
                'is_enabled' => true,
                'setting_value' => 'C:\\OCR_AutoUpload\\failed',
                'description' => 'Directory to move failed upload files',
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
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn('setting_value');
        });
    }
};
