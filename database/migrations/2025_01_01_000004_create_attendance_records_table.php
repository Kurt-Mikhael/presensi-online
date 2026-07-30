<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->date('attendance_date');

            $table->timestampTz('check_in_at')->nullable();
            $table->double('check_in_latitude', 18, 14)->nullable();
            $table->double('check_in_longitude', 18, 14)->nullable();
            $table->double('check_in_accuracy', 10, 4)->nullable();
            $table->boolean('check_in_is_inside_area')->nullable();

            $table->timestampTz('check_out_at')->nullable();
            $table->double('check_out_latitude', 18, 14)->nullable();
            $table->double('check_out_longitude', 18, 14)->nullable();
            $table->double('check_out_accuracy', 10, 4)->nullable();
            $table->boolean('check_out_is_inside_area')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'attendance_date'], 'attendance_records_user_date_unique');
            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};