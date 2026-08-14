<?php

namespace Tests\Unit;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceLocation;
use App\Repositories\LocationRepository;
use App\Services\AttendanceService;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class AttendanceServiceValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_stale_location_is_rejected(): void
    {
        $service = new AttendanceService(Mockery::mock(LocationRepository::class));
        $this->expectExceptionObject(new AttendanceException('LOCATION_STALE', __('presensi.errors.LOCATION_STALE')));
        $service->validateAgainstAreas(collect(), ['captured_at' => Carbon::now()->subMinute()->toIso8601String(), 'accuracy' => 1, 'latitude' => 0, 'longitude' => 0]);
    }

    public function test_point_inside_area_but_accuracy_too_low_is_rejected(): void
    {
        $area = new AttendanceLocation(['name' => 'Office', 'maximum_accuracy_meter' => 10]);
        $locations = Mockery::mock(LocationRepository::class);
        $locations->shouldReceive('validatePoint')->once()->andReturn(['inside' => true]);
        $service = new AttendanceService($locations);

        $this->expectExceptionObject(new AttendanceException('LOW_ACCURACY', __('presensi.errors.LOW_ACCURACY')));
        $service->validateAgainstAreas(collect([$area]), ['captured_at' => Carbon::now()->toIso8601String(), 'accuracy' => 11, 'latitude' => 0, 'longitude' => 0]);
    }
}
