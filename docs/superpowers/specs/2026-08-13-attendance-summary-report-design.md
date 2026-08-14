# Attendance Summary Report

## Goal

Add optional per-employee attendance summaries to the existing admin attendance page without changing the current detail table behavior.

## User Flow

1. Admin selects date interval and optional employee search as today.
2. Default `view=detail` keeps current per-day rows, correction controls, and existing export.
3. Admin activates `view=summary` using a button in the same filter panel.
4. Summary shows one row per employee for the selected interval.

## Summary Data

Each employee row contains:

- employee number and name;
- number of calendar records in selected interval;
- total completed work duration;
- total overtime duration;
- phase 1 total as hours and minutes;
- phase 2 total as hours and minutes;
- phase 3 total as hours and minutes.

Work duration counts only records with both check-in and check-out. Overtime totals use `overtime_phases[*]['minutes']`, which is the existing domain calculation source.

## Architecture

Keep `AdminAttendanceController` as route boundary. Add a focused aggregation method/class only if the current controller becomes harder to reason about; avoid a new dependency. Reuse current filtered employee and attendance data where possible. Use `view=summary` as a server-rendered mode so refresh, links, and export remain deterministic.

The export endpoint selects the same mode as the page. Detail export remains unchanged. Summary export contains one row per employee with totals and phase hour/minute columns.

## UI

- Add `Laporan Ringkasan` toggle beside search/export controls.
- Show active mode clearly.
- Preserve date and name filters when switching modes.
- On mobile, render one compact summary card per employee instead of forcing the wide desktop table.

## Validation and Safety

- Reuse existing date and search filters.
- Keep role middleware unchanged: only existing admin/superadmin attendance users can access it.
- Empty intervals return an empty summary without errors.
- Missing/incomplete attendance values contribute zero to completed work duration and overtime.

## Testing

- Summary aggregates multiple dates for one employee correctly.
- Incomplete records do not inflate work duration.
- Phase minute totals carry into hours correctly.
- Detail mode output remains available.
- Summary export uses summary rows and preserves filters.
