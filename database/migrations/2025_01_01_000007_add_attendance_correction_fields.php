<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('corrected_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
            $table->timestampTz('corrected_at')->nullable()->after('corrected_by');
            $table->text('correction_note')->nullable()->after('corrected_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['corrected_by']);
            $table->dropColumn(['corrected_by', 'corrected_at', 'correction_note']);
        });
    }
};
