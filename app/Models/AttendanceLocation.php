<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk presensi pengaturan area oleh admin.
 *
 * Kolom `geometry` dan `center_point` adalah kolom PostGIS (WKB binary).
 * Operasi baca/tulis geometri ditangani oleh {@see \App\Repositories\LocationRepository}
 * melalui raw query agar bisa memakai fungsi PostGIS (ST_AsGeoJSON, ST_GeomFromGeoJSON, dsb).
 *
 * @property int $id
 * @property string $name
 * @property string $area_type
 * @property float|null $radius_meter
 * @property float|null $maximum_accuracy_meter
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AttendanceLocation extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'geometry',
        'center_point',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'radius_meter' => 'float',
            'maximum_accuracy_meter' => 'float',
        ];
    }
}