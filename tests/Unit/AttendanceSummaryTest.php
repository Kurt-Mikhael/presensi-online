<?php

namespace Tests\Unit;

use App\Http\Controllers\AdminAttendanceController;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_aggregates_complete_records_and_carries_phase_minutes(): void
    {
        $user = User::create(['employee_number' => 'E-1', 'name' => 'Employee', 'is_active' => true]);
        $from = Carbon::parse('2026-08-08');

        AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => $from,
            'check_in_at' => $from->copy()->setTime(8, 0),
            'check_out_at' => $from->copy()->setTime(20, 0),
        ]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => $from->copy()->addDay(),
            'check_in_at' => $from->copy()->addDay()->setTime(8, 0),
            'check_out_at' => $from->copy()->addDay()->setTime(10, 0),
        ]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => $from->copy()->addDays(2),
            'check_in_at' => $from->copy()->addDays(2)->setTime(8, 0),
        ]);

        $method = new \ReflectionMethod(AdminAttendanceController::class, 'summaryRecords');
        $dailyMethod = new \ReflectionMethod(AdminAttendanceController::class, 'dailyRecords');
        $dailyRecords = $dailyMethod->invoke(new AdminAttendanceController, [
            'date_from' => '2026-08-08',
            'date_to' => '2026-08-10',
            'q' => null,
            'view' => 'summary',
        ]);
        $rows = $method->invoke(new AdminAttendanceController, $dailyRecords);

        $this->assertInstanceOf(Collection::class, $rows);
        $row = $rows->first();
        $this->assertSame(3, $row['days']);
        $this->assertSame(840, $row['work_minutes']);
        $this->assertSame('14 jam 00 menit', $row['work_duration']);
        $this->assertSame(780, $row['overtime_minutes']);
        $this->assertSame([
            ['hours' => 0, 'minutes' => 0],
            ['hours' => 9, 'minutes' => 0],
            ['hours' => 1, 'minutes' => 0],
            ['hours' => 2, 'minutes' => 0],
        ], $row['phases']);
    }
}
