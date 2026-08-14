<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Repositories\LocationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminLocationController extends Controller
{
    public function __construct(
        protected LocationRepository $locations,
    ) {}

    /**
     * GET /admin/location — halaman editor peta.
     */
    public function index(): View
    {
        $areas = $this->locations->all();

        return view('admin.location', [
            'areas' => $areas,
            'active' => $areas->firstWhere('is_active', true),
            'mapCenter' => [
                'lat' => (float) config('attendance.map.center_lat', -6.200100),
                'lng' => (float) config('attendance.map.center_lng', 106.816700),
                'zoom' => (int) config('attendance.map.zoom', 16),
            ],
            'defaultAccuracy' => (float) config('attendance.max_gps_accuracy_meter', 50),
            'workSettings' => AttendanceSetting::current(),
        ]);
    }

    public function updateWorkHours(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'work_start' => ['required', 'date_format:H:i'],
            'work_duration_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
        ]);

        AttendanceSetting::current()->update([
            'work_start' => $data['work_start'],
            'work_duration_hours' => $data['work_duration_hours'],
        ]);

        return back()->with('status', 'Pengaturan jam kerja berhasil disimpan.');
    }

    /**
     * GET /api/admin/location — daftar semua area (JSON).
     */
    public function show(): JsonResponse
    {
        $areas = $this->locations->all();

        return response()->json([
            'success' => true,
            'data' => $areas->map(fn ($a) => $this->serialize($a)),
        ]);
    }

    /**
     * PUT /api/admin/location — simpan area baru atau perbarui.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:attendance_locations,id'],
            'area_type' => ['required', 'in:circle,polygon'],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'maximum_accuracy_meter' => ['nullable', 'numeric', 'min:0', 'max:500'],

            'center.lat' => ['required_if:area_type,circle', 'numeric', 'between:-90,90'],
            'center.lng' => ['required_if:area_type,circle', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required_if:area_type,circle', 'numeric', 'min:1', 'max:50000'],

            'polygon' => ['required_if:area_type,polygon', 'array', 'min:3'],
            'polygon.*.lat' => ['required_with:polygon', 'numeric', 'between:-90,90'],
            'polygon.*.lng' => ['required_with:polygon', 'numeric', 'between:-180,180'],
        ]);

        $active = (bool) ($data['is_active'] ?? true);
        $maxAcc = isset($data['maximum_accuracy_meter']) ? (float) $data['maximum_accuracy_meter'] : null;
        $id = isset($data['id']) ? (int) $data['id'] : null;

        if ($data['area_type'] === 'circle') {
            $area = $id
                ? $this->locations->updateCircle($id, $data['name'], $data['center'], (float) $data['radius_meter'], $maxAcc, $active)
                : $this->locations->saveCircle($data['name'], $data['center'], (float) $data['radius_meter'], $maxAcc, $active);
        } else {
            $area = $id
                ? $this->locations->updatePolygon($id, $data['name'], $data['polygon'], $maxAcc, $active)
                : $this->locations->savePolygon($data['name'], $data['polygon'], $maxAcc, $active);
        }

        if (! $area) {
            return response()->json(['success' => false, 'message' => 'Lokasi tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lokasi presensi berhasil disimpan.',
            'data' => $this->serialize($area),
        ]);
    }

    /**
     * PATCH /api/admin/location/{id}/toggle — aktifkan/nonaktifkan area.
     */
    public function toggle(int $id, Request $request): JsonResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        $area = $this->locations->find($id);
        if (! $area) {
            return response()->json(['success' => false, 'message' => 'Lokasi tidak ditemukan.'], 404);
        }

        $this->locations->setActive($id, (bool) $request->boolean('is_active'));

        return response()->json(['success' => true, 'message' => 'Status lokasi diperbarui.']);
    }

    /**
     * DELETE /api/admin/location/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->locations->delete($id);

        return response()->json(['success' => true, 'message' => 'Lokasi dihapus.']);
    }

    public function serialize($area): array
    {
        return $this->locations->serialize($area);
    }
}
