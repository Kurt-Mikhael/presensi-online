<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exports\AttendanceXlsxExporter;

class AdminAttendanceController extends Controller
{
    private const PER_PAGE = 25;

    /**
     * GET /admin/attendance — halaman daftar presensi.
     */
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $dailyRecords = $this->dailyRecords($filters);
        $base = $dailyRecords;
        $stats = [
            'total' => (clone $base)->count(),
            'masuk' => (clone $base)->whereNotNull('check_in_at')->count(),
            'pulang' => (clone $base)->whereNotNull('check_out_at')->count(),
            'pending' => (clone $base)->whereNull('check_in_at')->count(),
        ];

        $records = $filters['view'] === 'detail'
            ? $this->paginateCollection($dailyRecords, $request)
            : collect();

        $summaryRows = $filters['view'] === 'summary'
            ? $this->paginateCollection($this->summaryRecords($dailyRecords), $request)
            : collect();

        return view('admin.attendance', [
            'records' => $records,
            'summaryRows' => $summaryRows,
            'filters' => $filters,
            'stats' => $stats,
            'workSettings' => AttendanceSetting::current(),
        ]);
    }

    /**
     * GET /admin/attendance/export — laporan presensi yang mengikuti filter.
     */
    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $dailyRecords = $this->dailyRecords($filters);
        $records = $filters['view'] === 'summary'
            ? $this->summaryRecords($dailyRecords)
            : $dailyRecords;
        $filename = 'laporan-presensi-'.now()->format('Y-m-d-His').'.xlsx';
        $exporter = app(AttendanceXlsxExporter::class);
        $path = $filters['view'] === 'summary'
            ? $exporter->createSummary($records)
            : $exporter->create($records);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function updateTimes(Request $request, User $user, string $date): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:save,cancel_check_in,cancel_check_out'],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date'],
            'correction_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['action'] !== 'save' && blank($data['correction_note'] ?? null)) {
            return back()->withErrors(['correction_note' => 'Catatan wajib diisi saat membatalkan absensi.']);
        }

        $attendanceDate = Carbon::createFromFormat('Y-m-d', $date);

        foreach (['check_in_at', 'check_out_at'] as $field) {
            if (! empty($data[$field]) && Carbon::parse($data[$field])->toDateString() !== $attendanceDate->toDateString()) {
                return back()->withErrors([$field => 'Waktu koreksi harus berada pada tanggal absensi.']);
            }
        }

        if ($data['action'] === 'save' && $data['check_in_at'] && $data['check_out_at']
            && strtotime($data['check_out_at']) < strtotime($data['check_in_at'])) {
            return back()->withErrors(['check_out_at' => 'Jam pulang harus setelah jam masuk.']);
        }

        $photoPathToDelete = null;
        DB::transaction(function () use ($data, $user, $attendanceDate, $request, &$photoPathToDelete): void {
            $record = AttendanceRecord::where('user_id', $user->id)
                ->where('attendance_date', $attendanceDate->toDateString())
                ->lockForUpdate()
                ->first();
            if (! $record) {
                AttendanceRecord::insertOrIgnore([
                    'user_id' => $user->id,
                    'attendance_date' => $attendanceDate->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $record = AttendanceRecord::where('user_id', $user->id)
                    ->where('attendance_date', $attendanceDate->toDateString())
                    ->lockForUpdate()
                    ->firstOrFail();
            }
            $updates = [
                'corrected_by' => $request->user()->id,
                'corrected_at' => now(),
                'correction_note' => $data['correction_note'] ?? null,
            ];

            if ($data['action'] === 'cancel_check_in') {
                $photoPathToDelete = $record->check_in_photo_path;
                $updates += [
                    'check_in_at' => null,
                    'check_in_latitude' => null,
                    'check_in_longitude' => null,
                    'check_in_accuracy' => null,
                    'check_in_is_inside_area' => null,
                    'check_in_photo_path' => null,
                    'check_in_photo_taken_at' => null,
                    'check_out_at' => null,
                    'check_out_latitude' => null,
                    'check_out_longitude' => null,
                    'check_out_accuracy' => null,
                    'check_out_is_inside_area' => null,
                ];
            } elseif ($data['action'] === 'cancel_check_out') {
                $updates += [
                    'check_out_at' => null,
                    'check_out_latitude' => null,
                    'check_out_longitude' => null,
                    'check_out_accuracy' => null,
                    'check_out_is_inside_area' => null,
                ];
            } else {
                $updates['check_in_at'] = $data['check_in_at'] ?? null;
                $updates['check_out_at'] = $data['check_out_at'] ?? null;
            }

            $record->fill($updates)->save();
        });

        if ($photoPathToDelete) {
            Storage::disk('local')->delete($photoPathToDelete);
        }

        return back()->with('status', 'Waktu absensi berhasil dikoreksi.');
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
            'view' => in_array($request->input('view'), ['detail', 'summary'], true)
                ? $request->input('view')
                : 'detail',
        ];
    }

    protected function dailyRecords(array $filters)
    {
        $dateFrom = Carbon::parse($filters['date_from']);
        $dateTo = Carbon::parse($filters['date_to']);
        $users = User::query()
            ->whereIn('role', ['employee', 'admin'])
            ->where('is_active', true)
            ->when($filters['q'], function ($query, $term) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $query->where(function ($userQuery) use ($term) {
                    $userQuery->where('name', 'ilike', $term)
                        ->orWhere('employee_number', 'ilike', $term);
                });
            })
            ->orderBy('name')
            ->get();

        $attendance = AttendanceRecord::query()
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo)
            ->get()
            ->keyBy(fn ($record) => $record->user_id.'|'.$record->attendance_date->toDateString());

        $records = collect();
        foreach ($users as $user) {
            for ($date = $dateTo->copy(); $date->gte($dateFrom); $date->subDay()) {
                $dateKey = $date->toDateString();
                $record = $attendance->get($user->id.'|'.$dateKey) ?? new AttendanceRecord([
                    'attendance_date' => $date->copy(),
                    'user_id' => $user->id,
                ]);

                $records->push($record->setRelation('user', $user));
            }
        }

        return $records;
    }

    protected function summaryRecords(Collection $dailyRecords): Collection
    {
        return $dailyRecords
            ->groupBy('user_id')
            ->map(function (Collection $records): array {
                $phaseMinutes = [0, 0, 0, 0];
                $workMinutes = 0;
                $overtimeMinutes = 0;

                foreach ($records as $record) {
                    if ($record->check_in_at && $record->check_out_at) {
                        $workMinutes += max(0, intdiv(
                            $record->check_out_at->getTimestamp() - $record->check_in_at->getTimestamp(),
                            60,
                        ));
                        $overtimeMinutes += $record->overtimeMinutes();

                        foreach ($record->overtime_phases as $index => $phase) {
                            if ($index < 4) {
                                $phaseMinutes[$index] += (int) ($phase['minutes'] ?? 0);
                            }
                        }
                    }
                }

                return [
                    'user' => $records->first()->user,
                    'days' => $records->count(),
                    'work_minutes' => $workMinutes,
                    'work_duration' => $this->formatMinutes($workMinutes),
                    'overtime_minutes' => $overtimeMinutes,
                    'overtime_duration' => $this->formatMinutes($overtimeMinutes),
                    'phases' => array_map(fn (int $minutes): array => [
                        'hours' => intdiv($minutes, 60),
                        'minutes' => $minutes % 60,
                    ], $phaseMinutes),
                ];
            })
            ->values();
    }

    protected function formatMinutes(int $minutes): string
    {
        return sprintf('%d jam %02d menit', intdiv($minutes, 60), $minutes % 60);
    }

    protected function paginateCollection($items, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, self::PER_PAGE)->values(),
            $items->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

}
