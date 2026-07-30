<?php

namespace App\Services;

use App\Models\AttendanceLocation;
use App\Repositories\LocationRepository;

/**
 * Membungkus pemeriksaan geofence PostGIS terhadap satu titik.
 * Logika SQL tetap di {@see LocationRepository::validatePoint()}.
 */
class GeofenceService
{
    public function __construct(protected LocationRepository $locations) {}

    /**
     * @return array{inside: bool, distance_meter: float|null}
     */
    public function validatePoint(AttendanceLocation $area, float $latitude, float $longitude): array
    {
        $result = $this->locations->validatePoint($area, $latitude, $longitude);

        return [
            'inside' => (bool) ($result['inside'] ?? false),
            'distance_meter' => isset($result['distance_meter']) ? (float) $result['distance_meter'] : null,
        ];
    }
}