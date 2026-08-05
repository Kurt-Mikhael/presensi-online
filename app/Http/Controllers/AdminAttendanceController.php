<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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

        $records = $this->paginateCollection($dailyRecords, $request);

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
        $filters = $this->filters($request);
        $records = $this->dailyRecords($filters);
        $filename = 'laporan-presensi-'.now()->format('Y-m-d-His').'.xlsx';
        $path = $this->createXlsx($records);

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

        $record = AttendanceRecord::firstOrNew([
            'user_id' => $user->id,
            'attendance_date' => $attendanceDate->toDateString(),
        ]);
        $updates = [
            'corrected_by' => $request->user()->id,
            'corrected_at' => now(),
            'correction_note' => $data['correction_note'] ?? null,
        ];

        if ($data['action'] === 'cancel_check_in') {
            $updates += [
                'check_in_at' => null,
                'check_in_latitude' => null,
                'check_in_longitude' => null,
                'check_in_accuracy' => null,
                'check_in_is_inside_area' => null,
                'check_in_photo_path' => null,
                'check_in_photo_taken_at' => null,
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
        ];
    }

    protected function dailyRecords(array $filters)
    {
        $dateFrom = Carbon::parse($filters['date_from']);
        $dateTo = Carbon::parse($filters['date_to']);
        $users = User::query()
            ->where('role', 'employee')
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
            ->with('user:id,employee_number,name,role')
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo)
            ->get()
            ->keyBy(fn ($record) => $record->user_id.'|'.$record->attendance_date->toDateString());

        $records = collect();
        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            foreach ($users as $user) {
                $dateKey = $date->toDateString();
                $record = $attendance->get($user->id.'|'.$dateKey) ?? new AttendanceRecord([
                    'attendance_date' => $date->copy(),
                    'user_id' => $user->id,
                ]);

                $records->push($record->setRelation('user', $user));
            }
        }

        return $records->sortByDesc('attendance_date')->values();
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

    protected function createXlsx($records): string
    {
        $path = tempnam(sys_get_temp_dir(), 'presensi-export-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Laporan Presensi" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);

        $headings = ['Tanggal', 'No. Pegawai', 'Nama Pegawai', 'Jam Masuk', 'Jam Pulang', 'Durasi Kerja', 'Lembur', 'Status'];
        $rows = [$headings];

        foreach ($records as $record) {
            $isWeekend = $record->attendance_date?->isWeekend();
            $rows[] = [
                $record->attendance_date?->format('Y-m-d'),
                $record->user?->employee_number,
                $record->user?->name,
                $record->check_in_at?->setTimezone(config('app.timezone'))?->format('H:i'),
                $record->check_out_at?->setTimezone(config('app.timezone'))?->format('H:i'),
                $record->work_duration ?? '-',
                $record->overtime_duration ?? '-',
                $isWeekend && ($record->check_in_at || $record->check_out_at)
                    ? 'Lembur'
                    : ($isWeekend
                        ? 'Hari Libur'
                        : ($record->check_in_at && $record->check_out_at
                            ? 'Lengkap'
                            : ($record->check_in_at ? 'Sudah Masuk' : 'Belum Presensi'))),
            ];
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $sheet .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $rowNumber => $row) {
            $sheet .= '<row r="'.($rowNumber + 1).'">';
            foreach ($row as $columnNumber => $value) {
                $cell = $this->excelColumn($columnNumber + 1).($rowNumber + 1);
                $value = htmlspecialchars((string) ($value ?? '-'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $sheet .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$value.'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $path;
    }

    protected function excelColumn(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $column = chr(65 + $remainder).$column;
            $number = intdiv($number - 1, 26);
        }

        return $column;
    }
}
