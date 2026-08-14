<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminAttendanceController;
use App\Models\AttendanceAttempt;
use App\Models\AttendanceSetting;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Repositories\LocationRepository;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AttendanceRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public function test_overtime_uses_actual_check_in_when_after_standard_work_start(): void
    {
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '08:00',
            'work_duration_hours' => 9,
        ]);
        $record = AttendanceRecord::create([
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(8, 30),
            'check_out_at' => Carbon::today()->setTime(18, 0),
        ]);

        $this->assertSame('0 jam 30 menit', $record->overtime_duration);
    }

    public function test_weekday_overtime_uses_first_phase_for_first_hour_then_second_phase(): void
    {
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '08:00',
            'work_duration_hours' => 9,
        ]);
        $date = Carbon::parse('2026-08-13');

        foreach ([30 => [30, 0], 60 => [60, 0], 120 => [60, 60]] as $extraMinutes => $expected) {
            $record = new AttendanceRecord([
                'attendance_date' => $date,
                'check_in_at' => $date->copy()->setTime(8, 0),
                'check_out_at' => $date->copy()->setTime(17, 0)->addMinutes($extraMinutes),
            ]);

            $phases = $record->overtime_phases;
            $this->assertCount(4, $phases);
            $this->assertSame($expected[0], $phases[0]['minutes']);
            $this->assertSame($expected[1], $phases[1]['minutes']);
            $this->assertSame(0, $phases[2]['minutes']);
            $this->assertSame(0, $phases[3]['minutes']);
            $this->assertSame($extraMinutes, array_sum(array_column($phases, 'minutes')));
        }
    }

    public function test_holiday_overtime_uses_four_phases_and_preserves_actual_duration(): void
    {
        $date = Carbon::parse('2026-08-08');
        foreach ([8 * 60 => [0, 480, 0, 0], 12 * 60 => [0, 480, 60, 180]] as $minutes => $expected) {
            $record = new AttendanceRecord([
                'attendance_date' => $date,
                'check_in_at' => $date->copy()->setTime(8, 0),
                'check_out_at' => $date->copy()->setTime(8, 0)->addMinutes($minutes),
            ]);

            $phases = $record->overtime_phases;
            $this->assertCount(4, $phases);
            $this->assertSame($expected, array_column($phases, 'minutes'));
            $this->assertSame($minutes, array_sum(array_column($phases, 'minutes')));
        }
    }

    public function test_overtime_uses_standard_work_start_when_check_in_is_early(): void
    {
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '08:00',
            'work_duration_hours' => 9,
        ]);
        $record = AttendanceRecord::create([
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(7, 30),
            'check_out_at' => Carbon::today()->setTime(18, 0),
        ]);

        $this->assertSame('1 jam 00 menit', $record->overtime_duration);
    }

    public function test_work_duration_uses_standard_baseline_for_standard_check_in(): void
    {
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '08:00',
            'work_duration_hours' => 9,
        ]);
        $record = AttendanceRecord::create([
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(8, 0),
            'check_out_at' => Carbon::today()->setTime(18, 0),
        ]);

        $this->assertSame('10 jam 00 menit', $record->work_duration);
    }

    public function test_work_duration_uses_standard_baseline_for_early_check_in(): void
    {
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '08:00',
            'work_duration_hours' => 9,
        ]);
        $record = AttendanceRecord::create([
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(7, 30),
            'check_out_at' => Carbon::today()->setTime(18, 0),
        ]);

        $this->assertSame('10 jam 00 menit', $record->work_duration);
    }

    public function test_work_duration_uses_actual_baseline_for_late_check_in(): void
    {
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '08:00',
            'work_duration_hours' => 9,
        ]);
        $record = AttendanceRecord::create([
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(8, 30),
            'check_out_at' => Carbon::today()->setTime(18, 0),
        ]);

        $this->assertSame('9 jam 30 menit', $record->work_duration);
    }

    public function test_checkout_before_standard_work_start_is_rejected_and_logged(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 59));
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '08:00',
            'work_duration_hours' => 9,
        ]);
        $user = User::create(['employee_number' => 'E-EARLY', 'name' => 'Employee']);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(7, 30),
        ]);
        $locations = Mockery::mock(LocationRepository::class);
        $locations->shouldNotReceive('getActiveLocations');
        $service = new AttendanceService($locations);
        $request = Request::create('/attendance/check-out', 'POST');
        $request->setUserResolver(fn () => $user);

        try {
            $service->checkOut($user, $this->payload(), $request);
            $this->fail('Expected check-out rejection.');
        } catch (\App\Exceptions\AttendanceException $exception) {
            $this->assertSame('BEFORE_WORK_START', $exception->errorCode);
            $this->assertSame(409, $exception->httpStatus);
        }

        $this->assertDatabaseHas('attendance_attempts', [
            'user_id' => $user->id,
            'attendance_type' => 'check_out',
            'is_success' => false,
            'failure_reason' => 'BEFORE_WORK_START',
        ]);
        $this->assertNull(AttendanceRecord::firstOrFail()->check_out_at);
    }

    public function test_failed_checkout_attempt_is_logged_after_transaction_rolls_back(): void
    {
        $user = User::create(['employee_number' => 'E-1', 'name' => 'Employee']);
        $payload = $this->payload();
        $request = Request::create('/attendance/check-out', 'POST');
        $request->setUserResolver(fn () => $user);

        $service = new AttendanceService(Mockery::mock(LocationRepository::class));

        try {
            $service->checkOut($user, $payload, $request);
            $this->fail('Expected check-out rejection.');
        } catch (\App\Exceptions\AttendanceException $exception) {
            $this->assertSame('CHECK_IN_REQUIRED', $exception->errorCode);
        }

        $this->assertDatabaseHas('attendance_attempts', [
            'user_id' => $user->id,
            'attendance_type' => 'check_out',
            'is_success' => false,
            'failure_reason' => 'CHECK_IN_REQUIRED',
        ]);
        $this->assertSame(1, AttendanceAttempt::count());
    }

    public function test_invalid_check_in_attempt_is_logged(): void
    {
        $user = User::create(['employee_number' => 'E-INVALID', 'name' => 'Employee']);
        $request = Request::create('/attendance/check-in', 'POST');
        $request->setUserResolver(fn () => $user);
        $service = new AttendanceService(Mockery::mock(LocationRepository::class));

        try {
            $service->checkIn($user, [], null, $request);
            $this->fail('Expected check-in rejection.');
        } catch (\App\Exceptions\AttendanceException $exception) {
            $this->assertSame('INVALID_LOCATION', $exception->errorCode);
        }

        $this->assertDatabaseHas('attendance_attempts', [
            'user_id' => $user->id,
            'attendance_type' => 'check_in',
            'is_success' => false,
            'failure_reason' => 'INVALID_LOCATION',
        ]);
    }

    public function test_successful_checkout_is_logged_once(): void
    {
        $user = User::create(['employee_number' => 'E-SUCCESS', 'name' => 'Employee']);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(8, 0),
        ]);
        $locations = Mockery::mock(LocationRepository::class);
        $locations->shouldReceive('getActiveLocations')->once()->andReturn(collect([
            new \App\Models\AttendanceLocation(['name' => 'Office', 'maximum_accuracy_meter' => 10]),
        ]));
        $locations->shouldReceive('validatePoint')->once()->andReturn(['inside' => true]);
        $service = new AttendanceService($locations);
        $request = Request::create('/attendance/check-out', 'POST');
        $request->setUserResolver(fn () => $user);

        $service->checkOut($user, $this->payload(), $request);

        try {
            $service->checkOut($user, $this->payload(), $request);
            $this->fail('Expected duplicate check-out rejection.');
        } catch (\App\Exceptions\AttendanceException $exception) {
            $this->assertSame('DUPLICATE_CHECK_OUT', $exception->errorCode);
        }

        $this->assertSame(2, AttendanceAttempt::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('attendance_attempts', [
            'user_id' => $user->id,
            'attendance_type' => 'check_out',
            'is_success' => true,
        ]);
        $this->assertDatabaseHas('attendance_attempts', [
            'user_id' => $user->id,
            'attendance_type' => 'check_out',
            'is_success' => false,
            'failure_reason' => 'DUPLICATE_CHECK_OUT',
        ]);
    }

    public function test_repeated_overtime_calculation_loads_attendance_settings_once_per_request(): void
    {
        AttendanceSetting::create([
            'id' => 1,
            'work_start' => '09:00',
            'work_duration_hours' => 9,
        ]);
        $record = AttendanceRecord::create([
            'attendance_date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(8, 0),
            'check_out_at' => Carbon::today()->setTime(19, 0),
        ]);
        $queries = 0;
        DB::listen(function ($query) use (&$queries): void {
            if (str_contains(strtolower($query->sql), 'attendance_settings')) {
                $queries++;
            }
        });

        $record->overtime_duration;
        $record->overtime_phases;

        $this->assertSame(1, $queries);
    }

    public function test_check_in_keeps_photo_when_success_audit_logging_fails_after_commit(): void
    {
        Storage::fake('local');
        $user = User::create(['employee_number' => 'E-PHOTO', 'name' => 'Employee']);
        $locations = Mockery::mock(LocationRepository::class);
        $locations->shouldReceive('getActiveLocations')->once()->andReturn(collect([
            new \App\Models\AttendanceLocation(['name' => 'Office', 'maximum_accuracy_meter' => 10]),
        ]));
        $locations->shouldReceive('validatePoint')->once()->andReturn(['inside' => true]);
        $service = Mockery::mock(AttendanceService::class, [$locations])->makePartial();
        $service->shouldAllowMockingProtectedMethods()
            ->shouldReceive('logAttempt')->twice()
            ->andReturnUsing(function (): void {
                static $calls = 0;
                if (++$calls === 1) {
                    throw new \RuntimeException('audit unavailable');
                }
            });
        $request = Request::create('/attendance/check-in', 'POST');
        $request->setUserResolver(fn () => $user);

        try {
            $service->checkIn($user, $this->payload(), UploadedFile::fake()->image('check-in.jpg'), $request);
            $this->fail('Expected audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $record = AttendanceRecord::firstOrFail();
        Storage::disk('local')->assertExists($record->check_in_photo_path);
    }

    public function test_cancelling_check_in_clears_existing_checkout_data(): void
    {
        $user = User::create(['employee_number' => 'E-2', 'name' => 'Employee']);
        $date = Carbon::today();
        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => $date,
            'check_in_at' => $date->copy()->setTime(8, 0),
            'check_out_at' => $date->copy()->setTime(17, 0),
            'check_out_latitude' => -6.2,
            'check_out_longitude' => 106.8,
            'check_out_accuracy' => 4.5,
            'check_out_is_inside_area' => true,
        ]);
        $request = Request::create('/admin/attendance', 'PATCH', [
            'action' => 'cancel_check_in',
            'correction_note' => 'Invalidated check-in',
        ]);
        $request->setUserResolver(fn () => $user);

        (new AdminAttendanceController)->updateTimes($request, $user, $date->toDateString());

        $record->refresh();
        $this->assertNull($record->check_in_at);
        $this->assertNull($record->check_out_at);
        $this->assertNull($record->check_out_latitude);
        $this->assertNull($record->check_out_longitude);
        $this->assertNull($record->check_out_accuracy);
        $this->assertNull($record->check_out_is_inside_area);
    }

    public function test_update_times_creates_missing_record_before_applying_updates(): void
    {
        $user = User::create(['employee_number' => 'E-MISSING', 'name' => 'Employee']);
        $date = Carbon::today();
        $request = Request::create('/admin/attendance', 'PATCH', [
            'action' => 'save',
            'check_in_at' => $date->copy()->setTime(8, 0)->toDateTimeString(),
            'check_out_at' => $date->copy()->setTime(17, 0)->toDateTimeString(),
        ]);
        $request->setUserResolver(fn () => $user);

        (new AdminAttendanceController)->updateTimes($request, $user, $date->toDateString());

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'attendance_date' => $date->toDateString(),
            'check_in_at' => $date->copy()->setTime(8, 0)->toDateTimeString(),
            'check_out_at' => $date->copy()->setTime(17, 0)->toDateTimeString(),
        ]);
    }

    private function payload(): array
    {
        return [
            'latitude' => 0,
            'longitude' => 0,
            'accuracy' => 1,
            'captured_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
