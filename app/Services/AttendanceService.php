<?php

namespace App\Services;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceAttempt;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\User;
use App\Repositories\LocationRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Seluruh aturan validasi presensi berjalan di sini (sisi server).
 * Frontend hanya mengirim data mentah; keputusan final ada di kelas ini.
 */
class AttendanceService
{
    /**
     * Area yang cocok pada validasi terakhir (untuk ditampilkan ke pengguna).
     */
    public function __construct(
        protected LocationRepository $locations,
    ) {}

    /**
     * Ambil atau buat record presensi hari ini untuk user.
     */
    public function getTodayRecord(User $user): AttendanceRecord
    {
        $query = AttendanceRecord::where('user_id', $user->id)
            ->where('attendance_date', today());

        $record = $query->first();
        if (! $record) {
            AttendanceRecord::insertOrIgnore([
                'user_id' => $user->id,
                'attendance_date' => today(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $record = $query->firstOrFail();
        }

        return $record;
    }

    /**
     * Lakukan presensi masuk.
     *
     * @param array{latitude: float, longitude: float, accuracy: float, captured_at: string} $payload
     * @throws AttendanceException
     */
    public function checkIn(User $user, array $payload, ?UploadedFile $photo, Request $request): AttendanceRecord
    {
        $photoPath = null;
        $transactionCompleted = false;

        try {
            $this->validateLocationPayload($payload);

            if (! $photo) {
                throw new AttendanceException('PHOTO_REQUIRED', __('presensi.errors.PHOTO_REQUIRED'), 422);
            }

            $record = DB::transaction(function () use ($payload, $user, $request, $photo, &$photoPath) {
                $record = AttendanceRecord::where('user_id', $user->id)
                    ->where('attendance_date', today())
                    ->lockForUpdate()
                    ->first();

                if (! $record) {
                    AttendanceRecord::insertOrIgnore([
                        'user_id' => $user->id,
                        'attendance_date' => today(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $record = AttendanceRecord::where('user_id', $user->id)
                        ->where('attendance_date', today())
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                if ($record->check_in_at !== null) {
                    throw new AttendanceException('DUPLICATE_CHECK_IN', __('presensi.errors.DUPLICATE_CHECK_IN'), 409);
                }

                try {
                    $matchedArea = $this->validateAgainstAreas($this->getLocationsOrFail(), $payload);
                } catch (AttendanceException $e) {
                    throw $e;
                }

                $photoPath = $this->storeCheckInPhoto($user, $photo);

                $record->update([
                    'check_in_at' => now(),
                    'check_in_latitude' => (float) $payload['latitude'],
                    'check_in_longitude' => (float) $payload['longitude'],
                    'check_in_accuracy' => (float) $payload['accuracy'],
                    'check_in_is_inside_area' => true,
                    'check_in_photo_path' => $photoPath,
                    'check_in_photo_taken_at' => now(),
                ]);

                return $record->fresh()->setAttribute('matched_area_name', $matchedArea->name);
            });
            $transactionCompleted = true;
            $this->logAttempt($user, 'check_in', $payload, true, null, $request);

            return $record;
        } catch (AttendanceException $e) {
            if (! $transactionCompleted && $photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            $this->logAttempt($user, 'check_in', $payload, false, $e->errorCode, $request);
            throw $e;
        } catch (\Throwable $e) {
            if (! $transactionCompleted && $photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            $this->logAttempt($user, 'check_in', $payload, false, 'INTERNAL_ERROR', $request);

            throw $e;
        }
    }

    /**
     * Lakukan presensi pulang.
     *
     * @param array{latitude: float, longitude: float, accuracy: float, captured_at: string} $payload
     * @throws AttendanceException
     */
    public function checkOut(User $user, array $payload, Request $request): AttendanceRecord
    {
        try {
            $this->validateLocationPayload($payload);

            $record = DB::transaction(function () use ($payload, $user, $request) {
                $record = AttendanceRecord::where('user_id', $user->id)
                ->where('attendance_date', today())
                ->lockForUpdate()
                ->first();

            if (! $record || $record->check_in_at === null) {
                throw new AttendanceException('CHECK_IN_REQUIRED', __('presensi.errors.CHECK_IN_REQUIRED'), 409);
            }

            if ($record->check_out_at !== null) {
                throw new AttendanceException('DUPLICATE_CHECK_OUT', __('presensi.errors.DUPLICATE_CHECK_OUT'), 409);
            }

            $currentTime = Carbon::now(config('app.timezone'));
            $workStart = $currentTime->copy()->setTimeFromTimeString(AttendanceSetting::current()->work_start);
            if ($currentTime->lt($workStart)) {
                throw new AttendanceException('BEFORE_WORK_START', __('presensi.errors.BEFORE_WORK_START'), 409);
            }

            try {
                $matchedArea = $this->validateAgainstAreas($this->getLocationsOrFail(), $payload);
            } catch (AttendanceException $e) {
                throw $e;
            }
            $record->update([
                'check_out_at' => now(),
                'check_out_latitude' => (float) $payload['latitude'],
                'check_out_longitude' => (float) $payload['longitude'],
                'check_out_accuracy' => (float) $payload['accuracy'],
                'check_out_is_inside_area' => true,
            ]);

            return $record->fresh()->setAttribute('matched_area_name', $matchedArea->name);
            });
            $this->logAttempt($user, 'check_out', $payload, true, null, $request);

            return $record;
        } catch (AttendanceException $e) {
            $this->logAttempt($user, 'check_out', $payload, false, $e->errorCode, $request);
            throw $e;
        } catch (\Throwable $e) {
            $this->logAttempt($user, 'check_out', $payload, false, 'INTERNAL_ERROR', $request);
            throw $e;
        }
    }

    /**
     * Validasi titik terhadap semua area aktif: presensi lolos jika titik
     * berada di dalam SALAH SATU area dan akurasi memenuhi batas area itu.
     *
     * @param \Illuminate\Support\Collection<int, AttendanceLocation> $areas
     * @param array{latitude: float, longitude: float, accuracy: float, captured_at: string} $payload
     * @return AttendanceLocation Area yang cocok dengan titik pengguna.
     * @throws AttendanceException
     */
    public function validateAgainstAreas($areas, array $payload): AttendanceLocation
    {
        if (! $this->checkFreshness($payload['captured_at'])) {
            throw new AttendanceException('LOCATION_STALE', __('presensi.errors.LOCATION_STALE'));
        }

        $accuracy = (float) $payload['accuracy'];
        $lat = (float) $payload['latitude'];
        $lng = (float) $payload['longitude'];
        $insideSomewhere = false;

        foreach ($areas as $area) {
            $result = $this->locations->validatePoint($area, $lat, $lng);

            if (! $result['inside']) {
                continue;
            }

            $insideSomewhere = true;
            $maxAccuracy = (float) ($area->maximum_accuracy_meter ?? config('attendance.max_gps_accuracy_meter'));

            if ($accuracy <= $maxAccuracy) {
                return $area;
            }
        }

        if ($insideSomewhere) {
            throw new AttendanceException('LOW_ACCURACY', __('presensi.errors.LOW_ACCURACY'));
        }

        throw new AttendanceException('OUTSIDE_AREA', __('presensi.errors.OUTSIDE_AREA'));
    }

    /**
     * Validasi dasar struktur dan rentang koordinat payload.
     *
     * @throws AttendanceException
     */
    protected function validateLocationPayload(array $payload): void
    {
        if (
            ! isset($payload['latitude'], $payload['longitude'], $payload['accuracy'], $payload['captured_at'])
            || ! is_numeric($payload['latitude'])
            || ! is_numeric($payload['longitude'])
            || ! is_numeric($payload['accuracy'])
        ) {
            throw new AttendanceException('INVALID_LOCATION', __('presensi.errors.INVALID_LOCATION'));
        }

        $lat = (float) $payload['latitude'];
        $lng = (float) $payload['longitude'];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new AttendanceException('INVALID_LOCATION', __('presensi.errors.INVALID_LOCATION'));
        }

        if ((float) $payload['accuracy'] < 0) {
            throw new AttendanceException('INVALID_LOCATION', __('presensi.errors.INVALID_LOCATION'));
        }
    }

    protected function checkFreshness(string $capturedAt): bool
    {
        try {
            $captured = Carbon::parse($capturedAt);
        } catch (\Throwable) {
            return false;
        }

        $maxAge = (int) config('attendance.max_location_age_seconds', 30);

        return abs($captured->getTimestamp() - now()->getTimestamp()) <= $maxAge;
    }

    /**
     * Semua area presensi yang aktif (multi-area).
     *
     * @return \Illuminate\Support\Collection<int, AttendanceLocation>
     */
    public function getActiveAreas()
    {
        return $this->locations->getActiveLocations();
    }

    /**
     * @return \Illuminate\Support\Collection<int, AttendanceLocation>
     * @throws AttendanceException
     */
    protected function getLocationsOrFail()
    {
        $areas = $this->locations->getActiveLocations();
        if ($areas->isEmpty()) {
            throw new AttendanceException('LOCATION_NOT_CONFIGURED', __('presensi.errors.LOCATION_NOT_CONFIGURED'));
        }

        return $areas;
    }

    protected function logAttempt(User $user, string $type, array $payload, bool $success, ?string $reason, Request $request): void
    {
        // Runs after attendance transaction has ended, so rollback cannot erase attempt log.
        DB::transaction(function () use ($user, $type, $payload, $success, $reason, $request): void {
            AttendanceAttempt::create([
                'user_id' => $user->id,
                'attendance_type' => $type,
                'latitude' => $payload['latitude'] ?? null,
                'longitude' => $payload['longitude'] ?? null,
                'accuracy' => $payload['accuracy'] ?? null,
                'is_success' => $success,
                'failure_reason' => $reason,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            ]);
        });
    }

    protected function storeCheckInPhoto(User $user, UploadedFile $photo): string
    {
        $path = sprintf(
            'attendance-photos/%d/%s/check-in-%s.jpg',
            $user->id,
            today()->format('Y/m/d'),
            now()->format('His')
        );

        return $photo->storeAs(dirname($path), basename($path), 'local');
    }
}
