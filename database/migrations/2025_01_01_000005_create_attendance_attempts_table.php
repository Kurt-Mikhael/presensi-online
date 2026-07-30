<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('attendance_type'); // check_in | check_out
            $table->double('latitude', 18, 14)->nullable();
            $table->double('longitude', 18, 14)->nullable();
            $table->double('accuracy', 10, 4)->nullable();
            $table->boolean('is_success')->default(false);
            $table->string('failure_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index('attendance_type');
        });

        DB::statement("ALTER TABLE attendance_attempts ADD CONSTRAINT attendance_attempts_type_check CHECK (attendance_type IN ('check_in','check_out'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin','employee'))");
        DB::statement("ALTER TABLE attendance_locations ADD CONSTRAINT attendance_locations_area_type_check CHECK (area_type IN ('circle','polygon'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_attempts');
    }
};