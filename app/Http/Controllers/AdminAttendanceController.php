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
        $filters = $this->filters($request);

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
     * GET /admin/attendance/export — laporan presensi yang mengikuti filter.
     */
    public function export(Request $request)
    {
        $filters = $this->filters($request, false);
        $records = $this->query($filters)->get();
        $filename = 'laporan-presensi-'.now()->format('Y-m-d-His').'.xls';

        return response()->streamDownload(function () use ($records) {
            echo "\xEF\xBB\xBF";
            echo '<table border="1"><thead><tr>';
            foreach (['Tanggal', 'No. Pegawai', 'Nama Pegawai', 'Jam Masuk', 'Jam Pulang', 'Durasi Kerja', 'Lembur', 'Status'] as $heading) {
                echo '<th>'.e($heading).'</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($records as $record) {
                $status = $record->check_in_at && $record->check_out_at
                    ? 'Lengkap'
                    : ($record->check_in_at ? 'Sudah Masuk' : 'Belum Presensi');
                $values = [
                    $record->attendance_date?->format('Y-m-d'),
                    $record->user?->employee_number,
                    $record->user?->name,
                    $record->check_in_at?->setTimezone(config('app.timezone'))?->format('H:i'),
                    $record->check_out_at?->setTimezone(config('app.timezone'))?->format('H:i'),
                    $record->work_duration ?? '-',
                    $record->overtime_duration ?? '-',
                    $status,
                ];

                echo '<tr>'.collect($values)->map(fn ($value) => '<td>'.e($value ?? '-').'</td>')->implode('').'</tr>';
            }

            echo '</tbody></table>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * GET /api/admin/attendance — daftar presensi (JSON, paginasi).
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $this->filters($request);

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

        if (! empty($filters['date_from'])) {
            $q->whereDate('attendance_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('attendance_date', '<=', $filters['date_to']);
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

    protected function filters(Request $request, bool $defaultToday = true): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $legacyDate = $request->input('date');

        if ($defaultToday && $dateFrom === null && $dateTo === null) {
            $dateFrom = $dateTo = now()->toDateString();
        } elseif ($dateFrom === null && $dateTo === null && $legacyDate !== null) {
            $dateFrom = $dateTo = $legacyDate;
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'q' => $request->input('q'),
        ];
    }
}
