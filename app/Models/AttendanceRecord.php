<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $guarded = ['id'];

    protected $appends = [
        'check_in_photo_url',
        'work_duration',
        'overtime_duration',
        'overtime_phases',
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
            'corrected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCheckInPhotoUrlAttribute(): ?string
    {
        return $this->check_in_photo_path
            ? route('attendance.photo', $this->id)
            : null;
    }

    public function getWorkDurationAttribute(): ?string
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return null;
        }

        $settings = $this->workSettings();
        $normalMinutes = (int) round($settings->work_duration_hours * 60);
        $effectiveStart = $this->expectedWorkEnd()->subMinutes($normalMinutes);
        $minutes = max(0, intdiv(
            $this->check_out_at->getTimestamp() - $effectiveStart->getTimestamp(),
            60
        ));

        return $this->formatDuration($minutes);
    }

    public function getOvertimeDurationAttribute(): ?string
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return null;
        }

        $minutes = $this->overtimeMinutes();

        return $minutes > 0 ? $this->formatDuration($minutes) : null;
    }

    public function getOvertimePhasesAttribute(): array
    {
        $overtimeMinutes = $this->overtimeMinutes();

        if ($this->attendance_date?->isWeekend()) {
            return $this->buildOvertimePhases($overtimeMinutes, [
                ['label' => 'Fase 1', 'rate' => '1.5x', 'limit' => 0],
                ['label' => 'Fase 2', 'rate' => '2x', 'limit' => 8 * 60],
                ['label' => 'Fase 3', 'rate' => '3x', 'limit' => 60],
                ['label' => 'Fase 4', 'rate' => '4x', 'limit' => 3 * 60],
            ]);
        }

        return $this->buildOvertimePhases($overtimeMinutes, [
            ['label' => 'Fase 1', 'rate' => '1.5x', 'limit' => 60],
            ['label' => 'Fase 2', 'rate' => '2x', 'limit' => PHP_INT_MAX],
            ['label' => 'Fase 3', 'rate' => '3x', 'limit' => PHP_INT_MAX],
            ['label' => 'Fase 4', 'rate' => '4x', 'limit' => PHP_INT_MAX],
        ]);
    }

    protected function expectedWorkEnd()
    {
        $settings = $this->workSettings();
        $checkIn = $this->check_in_at->copy()->setTimezone(config('app.timezone'));
        $workStart = $checkIn->copy()
            ->setTimeFromTimeString($settings->work_start);
        $normalStart = $checkIn->greaterThan($workStart) ? $checkIn : $workStart;

        return $normalStart
            ->addMinutes((int) round($settings->work_duration_hours * 60));
    }

    protected function workSettings(): AttendanceSetting
    {
        return once(fn () => AttendanceSetting::current());
    }

    public function overtimeMinutes(): int
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return 0;
        }

        if ($this->attendance_date?->isWeekend()) {
            return max(0, intdiv(
                $this->check_out_at->getTimestamp() - $this->check_in_at->getTimestamp(),
                60
            ) - 60);
        }

        return max(0, intdiv(
            $this->check_out_at->getTimestamp() - $this->expectedWorkEnd()->getTimestamp(),
            60
        ));
    }

    protected function buildOvertimePhases(int $minutes, array $phases): array
    {
        $result = [];

        foreach ($phases as $phase) {
            $phaseMinutes = min(max(0, $minutes), $phase['limit']);
            $result[] = [
                'label' => $phase['label'],
                'rate' => $phase['rate'],
                'minutes' => $phaseMinutes,
                'hours' => round($phaseMinutes / 60, 2),
                'duration' => $this->formatDuration($phaseMinutes),
            ];
            $minutes -= $phaseMinutes;
        }

        return $result;
    }

    protected function formatDuration(int $minutes): string
    {
        return sprintf('%d jam %02d menit', intdiv($minutes, 60), $minutes % 60);
    }
}
