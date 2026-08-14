# Attendance Architecture Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove identified correctness, security, performance, and responsibility-boundary problems without adding new framework dependencies.

**Architecture:** Keep Laravel MVC, Eloquent, `AttendanceService`, and `LocationRepository`. Make attendance writes transactional and locked, keep PostGIS as geofence authority, move XLSX generation into a focused exporter, and serve photos through an authorized controller from private storage.

**Tech Stack:** PHP 8.3, Laravel 11, PostgreSQL/PostGIS, PHPUnit 11, native `ZipArchive`.

**Spec:** User-approved chat design on 2026-08-13.

## Global Constraints

- Do not add dependencies.
- Backend remains final authority for geofence decisions.
- Preserve existing routes and response shapes unless security requires a change.
- Every non-trivial behavior change gets one focused automated check.

---

### Task 1: Lock Attendance Writes

**Files:**
- Modify: `app/Services/AttendanceService.php`
- Test: `tests/Feature/AttendanceConcurrencyTest.php`

**Interfaces:**
- `checkIn(User $user, array $payload, ?UploadedFile $photo, Request $request): AttendanceRecord`
- `checkOut(User $user, array $payload, Request $request): AttendanceRecord`

- [ ] Write tests proving duplicate check-in and duplicate check-out are rejected while the row is locked.
- [ ] Run `php artisan test tests/Feature/AttendanceConcurrencyTest.php` and confirm failure or missing test setup.
- [ ] Move record lookup and duplicate checks inside `DB::transaction()` using `lockForUpdate()`.
- [ ] Store private photos through the local disk and delete them with the same disk on transaction failure.
- [ ] Return matched area directly from service result or a response DTO-free tuple only if existing response compatibility requires it; remove mutable `lastMatchedArea` coupling.
- [ ] Run focused test and `php artisan test`.

### Task 2: Remove Geofence N+1

**Files:**
- Modify: `app/Repositories/LocationRepository.php`
- Test: `tests/Unit/LocationRepositoryTest.php`

- [ ] Add a repository test asserting active-location retrieval uses one query for rows plus computed GeoJSON, not one query per location.
- [ ] Replace per-row `hydrateLocation()` GeoJSON query with `ST_AsGeoJSON(...)` columns in the initial select.
- [ ] Keep `find()` and `all()` using the same hydration path.
- [ ] Run focused test and lint.

### Task 3: Split Attendance Export

**Files:**
- Create: `app/Exports/AttendanceXlsxExporter.php`
- Modify: `app/Http/Controllers/AdminAttendanceController.php`
- Test: `tests/Unit/AttendanceXlsxExporterTest.php`

- [ ] Move ZIP/XML workbook generation and Excel column conversion into `AttendanceXlsxExporter::create($records): string`.
- [ ] Preserve current columns, status labels, XML escaping, and temporary-file cleanup behavior.
- [ ] Make controller `export()` only build filters, query records, call exporter, and return download response.
- [ ] Test special XML characters and a generated XLSX containing expected headers.
- [ ] Run focused test and lint.

### Task 4: Secure Photo Access

**Files:**
- Modify: `app/Services/AttendanceService.php`
- Modify: `app/Models/AttendanceRecord.php`
- Modify: `app/Http/Controllers/AttendanceController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AttendancePhotoAccessTest.php`

- [ ] Store check-in photos on private/local disk, never public disk.
- [ ] Add an authenticated photo endpoint that only permits the owning employee or authorized admin roles.
- [ ] Return a route URL instead of `Storage::url()` for private photos.
- [ ] Ensure cancelled check-ins delete the old photo path.
- [ ] Test owner access, unauthorized employee denial, and admin access.

### Task 5: Restrict Trusted Proxies

**Files:**
- Modify: `bootstrap/app.php`
- Modify: `.env.example`
- Test: `tests/Feature/TrustedProxyTest.php` if middleware behavior is testable in current setup.

- [ ] Read trusted proxy addresses from `TRUSTED_PROXIES`, defaulting to no trusted proxy.
- [ ] Parse comma-separated values and pass them to `trustProxies()`.
- [ ] Document that `*` must not be used unless deployment controls every proxy.
- [ ] Run config/bootstrap tests and lint.

### Task 6: Remove Duplicate/Dead Abstractions

**Files:**
- Modify: `app/Http/Controllers/AttendanceController.php`
- Modify: `app/Services/GeofenceService.php` or remove it if all callers can use `LocationRepository` directly.
- Modify: `app/Repositories/LocationRepository.php`
- Modify: `app/Http/Controllers/AdminLocationController.php`

- [ ] Replace duplicate `validateCheckIn()` and `validateLocation()` rules with one request validation method that optionally requires a photo.
- [ ] Search all callers before removing `getActiveLocation()`, `deactivateAll()`, or the thin `GeofenceService`.
- [ ] Keep only methods used by routes/controllers/services.
- [ ] Run static search, tests, and lint.

### Task 7: Align Geofence Preview and Add Core Tests

**Files:**
- Modify: `resources/js/attendance.js`
- Create: `tests/Feature/AttendanceServiceTest.php`
- Create: `tests/Feature/RoleMiddlewareTest.php`
- Create: `tests/Feature/LoginFallbackTest.php`

- [ ] Make frontend circle boundary inclusive and document preview as advisory; retain backend PostGIS decision.
- [ ] Add tests for stale location, outside area, low accuracy, circle boundary, polygon boundary, and successful check-in/out.
- [ ] Add role middleware and local/master login fallback coverage using existing factories or minimal setup.
- [ ] Run `php artisan test` and `npm run build` if frontend scripts exist.

### Task 8: Final Verification

- [ ] Run `php artisan test`.
- [ ] Run `composer lint:test`.
- [ ] Run `php artisan route:list`.
- [ ] Inspect `git diff --check` and review changed files for unrelated edits.
