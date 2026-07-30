<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    private const PER_PAGE = 25;

    /**
     * GET /admin/attendance — halaman daftar presensi.
     */
    public function index(Request $request): View
    {
        $filters = [
            'date' => $request->input('date') ?: now()->toDateString(),
            'q' => $request->input('q'),
        ];

        $base = $this->query($filters);
        $stats = [
            'total' => (clone $base)->count(),
            'masuk' => (clone $base)->whereNotNull('check_in_at')->count(),
            'pulang' => (clone $base)->whereNotNull('check_out_at')->count(),
            'pending' => (clone $base)->whereNull('check_in_at')->count(),
        ];

        $records = (clone $base)->paginate(self::PER_PAGE)->withQueryString();

        return view('admin.attendance', [
            'records' => $records,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/admin/attendance — daftar presensi (JSON, paginasi).
     */
    public function list(Request $request): JsonResponse
    {
        $filters = [
            'date' => $request->input('date') ?: now()->toDateString(),
            'q' => $request->input('q'),
        ];

        $paginator = $this->query($filters)->paginate(self::PER_PAGE);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function query(array $filters)
    {
        $q = AttendanceRecord::query()
            ->with('user:id,employee_number,name,role')
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_at');

        if (! empty($filters['date'])) {
            $q->whereDate('attendance_date', $filters['date']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';
            $q->whereHas('user', function ($u) use ($term) {
                $u->where('name', 'ilike', $term)
                    ->orWhere('employee_number', 'ilike', $term);
            });
        }

        return $q;
    }
}