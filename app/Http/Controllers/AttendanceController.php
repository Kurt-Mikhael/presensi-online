<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Repositories\LocationRepository;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected LocationRepository $locations,
    ) {}

    /**
     * GET /attendance — halaman utama pegawai.
     */
    public function index(Request $request): View
    {
        $record = $this->attendance->getTodayRecord($request->user());
        $recent = $this->getHistoryRecords($request->user())
            ->take(5)
            ->values();

        $activeAreas = $this->attendance->getActiveAreas();

        return view('attendance.index', [
            'user' => $request->user(),
            'record' => $record,
            'recent' => $recent,
            'now' => now(),
            'activeAreas' => $activeAreas,
            'areas' => $activeAreas->map(fn ($a) => $this->locations->serialize($a))->values(),
            'workSettings' => AttendanceSetting::current(),
        ]);
    }

    /**
     * GET /attendance/history — seluruh riwayat presensi pegawai.
     */
    public function history(Request $request): View
    {
        $this->attendance->getTodayRecord($request->user());
        $records = $this->getHistoryRecords($request->user());

        return view('attendance.history', [
            'user' => $request->user(),
            'records' => $records,
        ]);
    }

    /**
     * GET /api/attendance/today
     */
    public function today(Request $request): JsonResponse
    {
        $record = $this->attendance->getTodayRecord($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'attendance_date' => $record->attendance_date?->format('Y-m-d'),
                'check_in_at' => $record->check_in_at?->toIso8601String(),
                'check_out_at' => $record->check_out_at?->toIso8601String(),
                'check_in_photo_url' => $record->check_in_photo_url,
                'check_in_photo_taken_at' => $record->check_in_photo_taken_at?->toIso8601String(),
                'has_check_in' => $record->check_in_at !== null,
                'has_check_out' => $record->check_out_at !== null,
                'history_version' => $request->user()->attendanceRecords()->max('updated_at'),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/attendance/check-in
     */
    public function checkIn(Request $request): JsonResponse
    {
        $payload = $this->validateCheckIn($request);
        $record = $this->attendance->checkIn($request->user(), $payload, $request->file('check_in_photo'), $request);

        return response()->json([
            'success' => true,
            'message' => __('presensi.success.check_in'),
            'data' => [
                'attendance_type' => 'check_in',
                'server_time' => $record->check_in_at?->toIso8601String(),
                'accuracy' => $record->check_in_accuracy,
                'area_name' => $this->attendance->lastMatchedArea?->name,
                'photo_url' => $record->check_in_photo_url,
                'photo_taken_at' => $record->check_in_photo_taken_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/attendance/check-out
     */
    public function checkOut(Request $request): JsonResponse
    {
        $payload = $this->validateLocation($request);
        $record = $this->attendance->checkOut($request->user(), $payload, $request);

        return response()->json([
            'success' => true,
            'message' => __('presensi.success.check_out'),
            'data' => [
                'attendance_type' => 'check_out',
                'server_time' => $record->check_out_at?->toIso8601String(),
                'accuracy' => $record->check_out_accuracy,
                'area_name' => $this->attendance->lastMatchedArea?->name,
            ],
        ]);
    }

    protected function validateCheckIn(Request $request): array
    {
        return $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'captured_at' => ['required', 'string'],
            'check_in_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ]);
    }

    protected function isVisibleHistoryRecord($record): bool
    {
        return $record->attendance_date?->isWeekday()
            || $record->check_in_at !== null
            || $record->check_out_at !== null;
    }

    protected function getHistoryRecords($user)
    {
        $storedRecords = $user->attendanceRecords()
            ->orderByDesc('attendance_date')
            ->get()
            ->keyBy(fn ($record) => $record->attendance_date->toDateString());

        $startDate = $storedRecords->pluck('attendance_date')->filter()->sort()->first();

        if (! $startDate) {
            return collect();
        }

        $records = collect();
        $today = today();

        for ($date = $startDate->copy(); $date->lte($today); $date->addDay()) {
            $dateKey = $date->toDateString();
            $record = $storedRecords->get($dateKey);

            if ($record && $this->isVisibleHistoryRecord($record)) {
                $records->push($record);
            } elseif ($date->isWeekday()) {
                $records->push(new AttendanceRecord([
                    'attendance_date' => $date->copy(),
                ]));
            }
        }

        return $records->reverse()->values();
    }

    protected function validateLocation(Request $request): array
    {
        return $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'captured_at' => ['required', 'string'],
        ]);
    }
}
