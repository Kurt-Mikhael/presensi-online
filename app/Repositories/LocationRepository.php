<?php

namespace App\Repositories;

use App\Models\AttendanceLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository khusus menangani kolom PostGIS (geometry/center_point) yang
 * tidak bisa diakses langsung oleh Eloquent karena bertipe biner WKB.
 *
 * Semua query geo memakai parameter binding untuk mencegah SQL injection.
 */
class LocationRepository
{
    /**
     * Ambil lokasi presensi aktif (versi awal: satu area saja).
     */
    public function getActiveLocation(): ?AttendanceLocation
    {
        $row = DB::table('attendance_locations')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first();

        if (! $row) {
            return null;
        }

        return $this->hydrateLocation($row);
    }

    /**
     * Ambil semua lokasi presensi yang aktif (multi-area).
     */
    public function getActiveLocations(): Collection
    {
        return DB::table('attendance_locations')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => $this->hydrateLocation($r));
    }

    public function find(int $id): ?AttendanceLocation
    {
        $row = DB::table('attendance_locations')->where('id', $id)->first();

        return $row ? $this->hydrateLocation($row) : null;
    }

    public function all(): Collection
    {
        return DB::table('attendance_locations')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($r) => $this->hydrateLocation($r));
    }

    /**
     * Validasi titik terhadap satu area presensi.
     *
     * @return array{inside: bool, distance_meter: float|null, matched: bool}
     */
public function validatePoint(AttendanceLocation $loc, float $latitude, float $longitude): array
    {
        $pointWkt = sprintf('POINT(%F %F)', $longitude, $latitude);

        if ($loc->area_type === 'circle') {
            $row = DB::selectOne(
                "SELECT
                    ST_DWithin(
                        l.center_point::geography,
                        ST_SetSRID(ST_GeomFromText(?), 4326)::geography,
                        ?
                    ) AS inside,
                    ST_Distance(
                        l.center_point::geography,
                        ST_SetSRID(ST_GeomFromText(?), 4326)::geography
                    ) AS distance_meter
                 FROM attendance_locations l
                 WHERE l.id = ?",
                [$pointWkt, (float) $loc->radius_meter, $pointWkt, $loc->id]
            );

            return $row ? (array) $row : ['inside' => false, 'distance_meter' => null];
        }

        $row = DB::selectOne(
            "SELECT
                ST_Covers(
                    l.geometry,
                    ST_SetSRID(ST_GeomFromText(?), 4326)
                ) AS inside,
                ST_Distance(
                    l.geometry::geography,
                    ST_SetSRID(ST_GeomFromText(?), 4326)::geography
                ) AS distance_meter
             FROM attendance_locations l
             WHERE l.id = ?",
            [$pointWkt, $pointWkt, $loc->id]
        );

        return $row ? (array) $row : ['inside' => false, 'distance_meter' => null];
    }

    /**
     * Simpan area lingkaran baru (multi-area: tidak mengubah status area lain).
     */
    public function saveCircle(string $name, array $center, float $radius, ?float $maxAccuracy, bool $active): AttendanceLocation
    {
        $pointWkt = sprintf('POINT(%F %F)', (float) $center['lng'], (float) $center['lat']);

        $id = DB::selectOne(
            "INSERT INTO attendance_locations
                (name, area_type, radius_meter, maximum_accuracy_meter, is_active, center_point, created_at, updated_at)
             VALUES (?, 'circle', ?, ?, ?, ST_GeomFromText(?, 4326), ?, ?)
             RETURNING id",
            [$name, $radius, $maxAccuracy, $active, $pointWkt, now(), now()]
        )->id;

        return $this->find((int) $id);
    }

    /**
     * Simpan area polygon dari array koordinat [lat, lng].
     */
    public function savePolygon(string $name, array $coords, ?float $maxAccuracy, bool $active): AttendanceLocation
    {
        $wkt = $this->buildPolygonWkt($coords);

        $id = DB::selectOne(
            "INSERT INTO attendance_locations
                (name, area_type, radius_meter, maximum_accuracy_meter, is_active, geometry, created_at, updated_at)
             VALUES (?, 'polygon', NULL, ?, ?, ST_GeomFromText(?, 4326), ?, ?)
             RETURNING id",
            [$name, $maxAccuracy, $active, $wkt, now(), now()]
        )->id;

        return $this->find((int) $id);
    }

    /**
     * Perbarui area lingkaran yang sudah ada.
     */
    public function updateCircle(int $id, string $name, array $center, float $radius, ?float $maxAccuracy, bool $active): ?AttendanceLocation
    {
        $pointWkt = sprintf('POINT(%F %F)', (float) $center['lng'], (float) $center['lat']);

        DB::statement(
            "UPDATE attendance_locations
             SET name = ?, area_type = 'circle', radius_meter = ?, maximum_accuracy_meter = ?,
                 is_active = ?, center_point = ST_GeomFromText(?, 4326), geometry = NULL, updated_at = ?
             WHERE id = ?",
            [$name, $radius, $maxAccuracy, $active, $pointWkt, now(), $id]
        );

        return $this->find($id);
    }

    /**
     * Perbarui area polygon yang sudah ada.
     */
    public function updatePolygon(int $id, string $name, array $coords, ?float $maxAccuracy, bool $active): ?AttendanceLocation
    {
        $wkt = $this->buildPolygonWkt($coords);

        DB::statement(
            "UPDATE attendance_locations
             SET name = ?, area_type = 'polygon', radius_meter = NULL, maximum_accuracy_meter = ?,
                 is_active = ?, geometry = ST_GeomFromText(?, 4326), center_point = NULL, updated_at = ?
             WHERE id = ?",
            [$name, $maxAccuracy, $active, $wkt, now(), $id]
        );

        return $this->find($id);
    }

    public function deactivateAll(): void
    {
        DB::table('attendance_locations')->where('is_active', true)->update(['is_active' => false]);
    }

    public function setActive(int $id, bool $active): void
    {
        DB::table('attendance_locations')->where('id', $id)->update([
            'is_active' => $active,
            'updated_at' => now(),
        ]);
    }

    public function delete(int $id): void
    {
        DB::table('attendance_locations')->where('id', $id)->delete();
    }

    /**
     * Memetakan baris DB (yang berisi kolom PostGIS biner) menjadi model + payload GeoJSON.
     */
    protected function hydrateLocation(object $row): AttendanceLocation
    {
        $loc = new AttendanceLocation();
        $loc->setRawAttributes((array) $row, true);
        $loc->exists = true;

        // Kolom PostGIS biner tidak dipakai langsung; sembunyikan kecuali geojson.
        $loc->setAttribute('geometry', null);
        $loc->setAttribute('center_point', null);

        $geo = DB::selectOne(
            'SELECT
                ST_AsGeoJSON(center_point) AS center_geojson,
                ST_AsGeoJSON(geometry)     AS polygon_geojson
            FROM attendance_locations WHERE id = ?',
            [$row->id]
        );

        $loc->setAttribute('center_geojson', $geo->center_geojson ?? null);
        $loc->setAttribute('polygon_geojson', $geo->polygon_geojson ?? null);

        return $loc;
    }

    /**
     * Ubah model lokasi menjadi array serializable (untuk JSON/Blade).
     */
    public function serialize(AttendanceLocation $area): array
    {
        $centerJson = $area->getAttribute('center_geojson');
        $center = $centerJson ? json_decode($centerJson, true) : null;

        $polygonJson = $area->getAttribute('polygon_geojson');
        $polygon = $polygonJson ? json_decode($polygonJson, true) : null;

        $polygonCoords = null;
        if (isset($polygon['coordinates'][0]) && is_array($polygon['coordinates'][0])) {
            $ring = $polygon['coordinates'][0];
            // Hilangkan titik penutup yang duplikat dengan titik pertama.
            if (count($ring) > 1 && $ring[0] === $ring[count($ring) - 1]) {
                array_pop($ring);
            }
            $polygonCoords = collect($ring)
                ->map(fn ($p) => ['lat' => $p[1], 'lng' => $p[0]])
                ->values();
        }

        return [
            'id' => $area->id,
            'name' => $area->name,
            'area_type' => $area->area_type,
            'radius_meter' => $area->radius_meter,
            'maximum_accuracy_meter' => $area->maximum_accuracy_meter,
            'is_active' => (bool) $area->is_active,
            'center' => isset($center['coordinates'])
                ? ['lat' => $center['coordinates'][1], 'lng' => $center['coordinates'][0]]
                : null,
            'polygon' => $polygonCoords,
            'created_at' => optional($area->created_at)?->toIso8601String(),
            'updated_at' => optional($area->updated_at)?->toIso8601String(),
        ];
    }

    protected function buildPolygonWkt(array $coords): string
    {
        $points = [];
        foreach ($coords as $c) {
            $lng = (float) ($c['lng'] ?? $c['lon'] ?? $c[1] ?? null);
            $lat = (float) ($c['lat'] ?? $c[0] ?? null);
            $points[] = sprintf('%F %F', $lng, $lat);
        }

        if (count($points) < 3) {
            throw new \InvalidArgumentException('Polygon minimal terdiri dari 3 titik.');
        }

        if ($points[0] !== end($points)) {
            $points[] = $points[0];
        }

        return sprintf('POLYGON((%s))', implode(', ', $points));
    }
}