<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('work_start', 5)->default('09:00');
            $table->decimal('work_duration_hours', 4, 2)->default(9);
            $table->timestamps();
        });

        DB::table('attendance_settings')->insert([
            'id' => 1,
            'work_start' => config('attendance.work_start', '09:00'),
            'work_duration_hours' => (float) config('attendance.work_duration_hours', 9),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
