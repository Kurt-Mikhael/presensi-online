<?php

namespace App\Exports;

class AttendanceXlsxExporter
{
    public function create(iterable $records): string
    {
        return $this->createRows(
            ['Tanggal', 'No. Pegawai', 'Nama Pegawai', 'Jam Masuk', 'Jam Pulang', 'Durasi Kerja', 'Lembur', 'Fase 1 (jam)', 'Fase 1 (menit)', 'Fase 2 (jam)', 'Fase 2 (menit)', 'Fase 3 (jam)', 'Fase 3 (menit)', 'Fase 4 (jam)', 'Fase 4 (menit)', 'Status'],
            (function () use ($records): iterable {
                foreach ($records as $record) {
                    $weekend = $record->attendance_date?->isWeekend();
                    yield [
                        $record->attendance_date?->format('Y-m-d'), $record->user?->employee_number, $record->user?->name,
                        $record->check_in_at?->setTimezone(config('app.timezone'))?->format('H:i'), $record->check_out_at?->setTimezone(config('app.timezone'))?->format('H:i'),
                        $record->work_duration ?? '-', $record->overtime_duration ?? '-',
                        $record->overtime_phases[0]['hours'] ?? 0, $record->overtime_phases[0]['minutes'] ?? 0,
                        $record->overtime_phases[1]['hours'] ?? 0, $record->overtime_phases[1]['minutes'] ?? 0,
                        $record->overtime_phases[2]['hours'] ?? 0, $record->overtime_phases[2]['minutes'] ?? 0,
                        $record->overtime_phases[3]['hours'] ?? 0, $record->overtime_phases[3]['minutes'] ?? 0,
                        $weekend && ($record->check_in_at || $record->check_out_at) ? 'Lembur' : ($weekend ? 'Hari Libur' : ($record->check_in_at && $record->check_out_at ? 'Lengkap' : ($record->check_in_at ? 'Sudah Masuk' : 'Belum Presensi'))),
                    ];
                }
            })(),
        );
    }

    public function createSummary(iterable $rows): string
    {
        return $this->createRows(
            ['No. Pegawai', 'Nama Pegawai', 'Hari', 'Durasi Kerja', 'Lembur', 'Fase 1 (jam)', 'Fase 1 (menit)', 'Fase 2 (jam)', 'Fase 2 (menit)', 'Fase 3 (jam)', 'Fase 3 (menit)', 'Fase 4 (jam)', 'Fase 4 (menit)'],
            (function () use ($rows): iterable {
                foreach ($rows as $row) {
                    yield [
                        $row['user']?->employee_number, $row['user']?->name, $row['days'], $row['work_duration'], $row['overtime_duration'],
                        $row['phases'][0]['hours'] ?? 0, $row['phases'][0]['minutes'] ?? 0,
                        $row['phases'][1]['hours'] ?? 0, $row['phases'][1]['minutes'] ?? 0,
                        $row['phases'][2]['hours'] ?? 0, $row['phases'][2]['minutes'] ?? 0,
                        $row['phases'][3]['hours'] ?? 0, $row['phases'][3]['minutes'] ?? 0,
                    ];
                }
            })(),
        );
    }

    private function createRows(array $headers, iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'presensi-export-');
        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary export file.');
        }

        $zip = new \ZipArchive;
        $opened = false;
        $keepFile = false;

        try {
            if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to open temporary export file as ZIP.');
            }
            $opened = true;
            $add = static function (string $name, string $contents) use ($zip): void {
                if (! $zip->addFromString($name, $contents)) {
                    throw new \RuntimeException("Unable to add {$name} to export.");
                }
            };
            $add('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $add('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $add('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Presensi" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $add('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

            $rows = [$headers, ...iterator_to_array($rows, false)];

            $sheet = '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
            foreach ($rows as $r => $row) {
                $sheet .= '<row r="'.($r + 1).'">';
                foreach ($row as $c => $value) {
                    $value = htmlspecialchars((string) ($value ?? '-'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $sheet .= '<c r="'.$this->column($c + 1).($r + 1).'" t="inlineStr"><is><t>'.$value.'</t></is></c>';
                }
                $sheet .= '</row>';
            }
            $add('xl/worksheets/sheet1.xml', $sheet.'</sheetData></worksheet>');
            if (! $zip->close()) {
                throw new \RuntimeException('Unable to finalize XLSX export.');
            }
            $opened = false;
            $keepFile = true;
            return $path;
        } finally {
            if ($opened) {
                $zip->close();
            }
            if (! $keepFile) {
                @unlink($path);
            }
        }
    }

    protected function column(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $column = chr(65 + (($number - 1) % 26)).$column;
            $number = intdiv($number - 1, 26);
        }
        return $column;
    }
}
