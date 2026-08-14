# Overtime Multiplier Phases Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace three time buckets with four overtime multiplier categories across domain calculations, detail/summary reports, and Excel exports.

**Architecture:** Keep `AttendanceRecord` as the source of overtime phase calculation. Always return four phase entries with zero-filled unused categories. Update existing Blade and exporter consumers to iterate four phases without changing total overtime minutes.

**Tech Stack:** PHP 8.3, Laravel 11, Blade, Tailwind CSS, PHPUnit, native ZipArchive.

**Spec:** `docs/superpowers/specs/2026-08-14-overtime-multiplier-phases-design.md`

## Global Constraints

- Fase 1 = 1.5x, Fase 2 = 2x, Fase 3 = 3x, Fase 4 = 4x.
- Weekday: first 60 overtime minutes in Fase 1, remaining minutes in Fase 2, Fase 3/4 zero.
- Holiday/weekend: first 480 minutes in Fase 2, next 60 in Fase 3, next 180 in Fase 4, Fase 1 zero.
- Existing overtime duration remains total actual overtime minutes.
- Existing conditional overtime baseline and work-duration rules remain unchanged.

---

### Task 1: Update Overtime Domain Calculation

**Files:**
- Modify: `app/Models/AttendanceRecord.php`
- Test: `tests/Feature/AttendanceRegressionTest.php`

- [ ] Add failing tests for weekday 30/60/120 minutes, holiday 8 hours, holiday 12 hours, and four zero-filled phase entries.
- [ ] Run focused tests and confirm expected failures if PHP is available.
- [ ] Update phase definitions to emit four entries with exact rates and limits.
- [ ] Keep total overtime duration based on actual minutes and retain conditional baseline.
- [ ] Run focused tests and lint.

### Task 2: Update Detail and Summary Reports

**Files:**
- Modify: `resources/views/admin/attendance.blade.php`
- Modify: `app/Http/Controllers/AdminAttendanceController.php`
- Test: `tests/Unit/AttendanceSummaryTest.php`

- [ ] Change detail phase loops from `[0, 1, 2]` to `[0, 1, 2, 3]` and render Fase 4.
- [ ] Preserve detail phase hour/minute columns and update detail colspan counts.
- [ ] Aggregate four phase indexes in summary rows.
- [ ] Render Fase 4 in desktop summary table and mobile summary cards.
- [ ] Test phase 4 aggregation and zero-filled phases.

### Task 3: Update Excel Exports

**Files:**
- Modify: `app/Exports/AttendanceXlsxExporter.php`
- Test: `tests/Unit/AttendanceXlsxExporterTest.php`
- Test: `tests/Unit/AttendanceSummaryExportTest.php`

- [ ] Keep detail export total columns and add Fase 4 hour/minute columns.
- [ ] Add Fase 4 to summary export headers and row values.
- [ ] Test generated XML contains Fase 4 headers and values.

### Task 4: Verification

- [ ] Run `php artisan test tests/Feature/AttendanceRegressionTest.php tests/Unit/AttendanceSummaryTest.php tests/Unit/AttendanceXlsxExporterTest.php tests/Unit/AttendanceSummaryExportTest.php`.
- [ ] Run `composer lint:test`.
- [ ] Run `npm run build`.
- [ ] Run `git diff --check`.
- [ ] Verify no consumer still assumes exactly three phases.
