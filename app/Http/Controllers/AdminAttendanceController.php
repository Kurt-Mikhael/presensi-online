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
        $filename = 'laporan-presensi-'.now()->format('Y-m-d-His').'.xlsx';
        $path = $this->createXlsx($records);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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
            $rows[] = [
                $record->attendance_date?->format('Y-m-d'),
                $record->user?->employee_number,
                $record->user?->name,
                $record->check_in_at?->setTimezone(config('app.timezone'))?->format('H:i'),
                $record->check_out_at?->setTimezone(config('app.timezone'))?->format('H:i'),
                $record->work_duration ?? '-',
                $record->overtime_duration ?? '-',
                $record->check_in_at && $record->check_out_at
                    ? 'Lengkap'
                    : ($record->check_in_at ? 'Sudah Masuk' : 'Belum Presensi'),
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
