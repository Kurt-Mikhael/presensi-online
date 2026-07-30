<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $exists = DB::table('attendance_locations')->exists();

        if ($exists) {
            return;
        }

        // Lokasi demo: lingkaran di sekitar pusat default konfigurasi, radius 150 m.
        $centerLat = (float) config('attendance.map.center_lat', -6.200100);
        $centerLng = (float) config('attendance.map.center_lng', 106.816700);
        $wkt = sprintf('POINT(%F %F)', $centerLng, $centerLat);

        DB::statement(
            "INSERT INTO attendance_locations
                (name, area_type, radius_meter, maximum_accuracy_meter, is_active, center_point, created_at, updated_at)
             VALUES (?, 'circle', 150, 50, TRUE, ST_GeomFromText(?, 4326), NOW(), NOW())",
            ['Kantor Pusat (demo)', $wkt]
        );
    }
}