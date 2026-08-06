<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $table = 'attendance_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'work_duration_hours' => 'float',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'work_start' => config('attendance.work_start', '09:00'),
                'work_duration_hours' => (float) config('attendance.work_duration_hours', 9),
            ],
        );
    }

    public function workEndMinutes(): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $this->work_start));

        return ($hours * 60) + $minutes + (int) round($this->work_duration_hours * 60);
    }

    public function workEnd(): string
    {
        $minutes = $this->workEndMinutes() % (24 * 60);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
