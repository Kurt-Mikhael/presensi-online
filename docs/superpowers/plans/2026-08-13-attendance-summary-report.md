# Attendance Summary Report Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add same-page employee summary mode with interval totals while preserving existing detail mode and export.

**Architecture:** Use `view=summary` as server-rendered mode in `AdminAttendanceController`. Reuse filtered users and attendance records, aggregate completed work minutes and existing overtime phase minutes in a focused method, and render desktop/mobile summary views in the existing Blade page. Export follows active mode.

**Tech Stack:** Laravel 11, PHP 8.3, Blade, Tailwind CSS, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-08-13-attendance-summary-report-design.md`

## Global Constraints

- Default `view=detail` keeps current per-day rows, correction controls, and existing export.
- Work duration counts only records with both check-in and check-out.
- Overtime totals use `overtime_phases[*]['minutes']`.
- Keep role middleware unchanged.
- Do not add dependencies.

---

### Task 1: Aggregate Summary Data

**Files:**
- Modify: `app/Http/Controllers/AdminAttendanceController.php`
- Test: `tests/Unit/AttendanceSummaryTest.php`

**Interfaces:**
- `filters(Request $request, bool $defaultToday = true): array` gains `view` with `detail` default.
- `summaryRecords(array $filters): Collection` returns one array/model payload per employee with totals.

- [ ] Add unit coverage for multiple dates, incomplete attendance, and phase minute carry-over.
- [ ] Add `view` filter validation to allow only `detail` and `summary`.
- [ ] Build one summary row per filtered active employee from existing attendance records.
- [ ] Sum completed work minutes from check-in/check-out timestamps.
- [ ] Sum overtime phase `minutes`, derive total overtime minutes, and expose each phase as `{hours, minutes}`.
- [ ] Pass `view` and summary rows from `index()` without changing detail mode data.
- [ ] Run focused tests and lint if PHP is available.

### Task 2: Render Summary Mode

**Files:**
- Modify: `resources/views/admin/attendance.blade.php`

- [ ] Add `Laporan Detail` and `Laporan Ringkasan` controls preserving current query filters.
- [ ] Keep current detail table behind `@if($filters['view'] === 'detail')`.
- [ ] Add desktop summary table with employee, days, work duration, overtime, and phase hour/minute columns.
- [ ] Add mobile summary cards with the same values.
- [ ] Keep correction/photo actions only in detail mode.
- [ ] Show active mode visually.

### Task 3: Mode-Aware Export

**Files:**
- Modify: `app/Http/Controllers/AdminAttendanceController.php`
- Modify: `app/Exports/AttendanceXlsxExporter.php`
- Test: `tests/Unit/AttendanceSummaryExportTest.php`

- [ ] Pass request `view` to export filter handling.
- [ ] Keep current detail export columns unchanged.
- [ ] Add summary export rows with totals and phase hour/minute columns.
- [ ] Use separate exporter method `createSummary(iterable $rows): string` without changing `create()` detail behavior.
- [ ] Test summary export headers and aggregated values.

### Task 4: Verification

- [ ] Run `php artisan test`.
- [ ] Run `composer lint:test`.
- [ ] Run `npm run build`.
- [ ] Run `git diff --check`.
- [ ] Review detail mode and summary mode routes/links for preserved filters.
