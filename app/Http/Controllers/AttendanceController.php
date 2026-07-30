<?php

namespace App\Http\Controllers;

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
        $recent = $request->user()->attendanceRecords()
            ->where('attendance_date', '<', today())
            ->orderByDesc('attendance_date')
            ->limit(5)
            ->get();

        $activeAreas = $this->attendance->getActiveAreas();

        return view('attendance.index', [
            'user' => $request->user(),
            'record' => $record,
            'recent' => $recent,
            'now' => now(),
            'activeAreas' => $activeAreas,
            'areas' => $activeAreas->map(fn ($a) => $this->locations->serialize($a))->values(),
        ]);
    }

    /**
     * GET /attendance/history — seluruh riwayat presensi pegawai.
     */
    public function history(Request $request): View
    {
        $records = $request->user()->attendanceRecords()
            ->orderByDesc('attendance_date')
            ->paginate(10);

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