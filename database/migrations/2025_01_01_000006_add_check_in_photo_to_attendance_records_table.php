<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('check_in_photo_path')->nullable()->after('check_in_is_inside_area');
            $table->timestampTz('check_in_photo_taken_at')->nullable()->after('check_in_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['check_in_photo_path', 'check_in_photo_taken_at']);
        });
    }
};