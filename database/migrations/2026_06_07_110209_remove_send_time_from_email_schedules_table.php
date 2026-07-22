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
            $table->dropColumn('send_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_schedules', function (Blueprint $table) {
            $table->time('send_time')->default('06:00')->after('frequency');
        });
    }
};
