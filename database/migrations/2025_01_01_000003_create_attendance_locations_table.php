<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            // area_type: 'circle' atau 'polygon'
            $table->string('area_type')->default('circle');
            // geometry: polygon (PostGIS)
            // center_point: point (PostGIS) untuk pusat lingkaran
            // radius_meter: jari-jari lingkaran dalam meter
            $table->decimal('radius_meter', 10, 2)->nullable();
            $table->decimal('maximum_accuracy_meter', 8, 2)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Kolom PostGIS ditambahkan via raw statement (Laravel tidak punya tipe geometry p Postgres).
        DB::statement("ALTER TABLE attendance_locations ADD COLUMN center_point geometry(Point, 4326)");
        DB::statement("ALTER TABLE attendance_locations ADD COLUMN geometry geometry(Polygon, 4326)");

        // Indeks GiST untuk validasi geofence yang cepat.
        DB::statement("CREATE INDEX attendance_locations_geometry_gist ON attendance_locations USING GIST (geometry)");
        DB::statement("CREATE INDEX attendance_locations_center_gist ON attendance_locations USING GIST (center_point)");
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_locations');
    }
};