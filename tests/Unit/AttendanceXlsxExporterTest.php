<?php

namespace Tests\Unit;

use App\Exports\AttendanceXlsxExporter;
use Tests\TestCase;

class AttendanceXlsxExporterTest extends TestCase
{
    public function test_export_contains_headers_and_escaped_xml(): void
    {
        $record = new class
        {
            public $attendance_date;
            public $user;
            public $check_in_at = null;
            public $check_out_at = null;
            public $work_duration = null;
            public $overtime_duration = null;
            public $overtime_phases = [
                ['hours' => 0], ['hours' => 0], ['hours' => 0], ['hours' => 3],
            ];
        };
        $record->user = (object) ['employee_number' => 'E<&', 'name' => 'A<&'];

        $path = (new AttendanceXlsxExporter)->create([$record]);
        $zip = new \ZipArchive;
        self::assertSame(true, $zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        self::assertStringContainsString('Tanggal', $sheet);
        self::assertStringContainsString('Fase 4 (jam)', $sheet);
        self::assertStringContainsString('Fase 4 (menit)', $sheet);
        self::assertStringContainsString('>3</t>', $sheet);
        self::assertStringContainsString('A&lt;&amp;', $sheet);
        self::assertStringNotContainsString('A<&', $sheet);
    }
}
