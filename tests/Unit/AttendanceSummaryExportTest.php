<?php

namespace Tests\Unit;

use App\Exports\AttendanceXlsxExporter;
use Tests\TestCase;

class AttendanceSummaryExportTest extends TestCase
{
    public function test_summary_export_contains_headers_and_totals(): void
    {
        $path = (new AttendanceXlsxExporter)->createSummary([[
            'user' => (object) ['employee_number' => 'E-1', 'name' => 'Employee'],
            'days' => 3,
            'work_duration' => '14 jam 00 menit',
            'overtime_duration' => '14 jam 00 menit',
            'phases' => [
                ['hours' => 10, 'minutes' => 0],
                ['hours' => 1, 'minutes' => 0],
                ['hours' => 3, 'minutes' => 0],
                ['hours' => 4, 'minutes' => 30],
            ],
        ]]);

        $zip = new \ZipArchive;
        self::assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        self::assertStringContainsString('No. Pegawai', $sheet);
        self::assertStringContainsString('Fase 3 (menit)', $sheet);
        self::assertStringContainsString('Fase 4 (jam)', $sheet);
        self::assertStringContainsString('>4</t>', $sheet);
        self::assertStringContainsString('E-1', $sheet);
        self::assertStringContainsString('14 jam 00 menit', $sheet);
        self::assertStringContainsString('10', $sheet);
    }
}
