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
        Schema::table('email_schedules', function (Blueprint $table) {
            $table->time('send_time')->default('06:00:00')->after('frequency');
            $table->dropForeign(['ocr_template_id']);
            $table->dropColumn('ocr_template_id');
        });

        Schema::table('email_schedules', function (Blueprint $table) {
            $table->json('ocr_template_ids')->nullable()->after('send_time');
        });
    }

    public function down(): void
    {
        Schema::table('email_schedules', function (Blueprint $table) {
            $table->dropColumn(['send_time', 'ocr_template_ids']);
        });

        Schema::table('email_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('ocr_template_id')->nullable();
            $table->foreign('ocr_template_id')->references('id')->on('ocr_templates')->onDelete('set null');
        });
    }
};
