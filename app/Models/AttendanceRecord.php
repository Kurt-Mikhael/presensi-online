<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AttendanceRecord extends Model
{
    protected $guarded = ['id'];

    protected $appends = [
        'check_in_photo_url',
        'work_duration',
        'overtime_duration',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_in_photo_taken_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy' => 'float',
            'check_in_is_inside_area' => 'boolean',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy' => 'float',
            'check_out_is_inside_area' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCheckInPhotoUrlAttribute(): ?string
    {
        return $this->check_in_photo_path
            ? Storage::disk('public')->url($this->check_in_photo_path)
            : null;
    }

    public function getWorkDurationAttribute(): ?string
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return null;
        }

        $minutes = max(0, intdiv(
            $this->check_out_at->getTimestamp() - $this->check_in_at->getTimestamp(),
            60
        ));

        return $this->formatDuration($minutes);
    }

    public function getOvertimeDurationAttribute(): ?string
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return null;
        }

        $expectedEnd = $this->expectedWorkEnd();
        $minutes = max(0, intdiv(
            $this->check_out_at->getTimestamp() - $expectedEnd->getTimestamp(),
            60
        ));

        return $minutes > 0 ? $this->formatDuration($minutes) : null;
    }

    protected function expectedWorkEnd()
    {
        $checkIn = $this->check_in_at->copy()->setTimezone(config('app.timezone'));
        $workStart = $checkIn->copy()->setTimeFromTimeString(config('attendance.work_start', '09:00'));
        $workHours = (int) config('attendance.work_duration_hours', 9);

        return ($checkIn->greaterThan($workStart) ? $checkIn : $workStart)->addHours($workHours);
    }

    protected function formatDuration(int $minutes): string
    {
        return sprintf('%d jam %02d menit', intdiv($minutes, 60), $minutes % 60);
    }
}
