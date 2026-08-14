# Overtime Multiplier Phases

## Goal

Represent overtime as four multiplier categories while preserving actual overtime minutes and applying the correct category windows for weekdays and holidays.

## Phase Categories

| Phase | Multiplier | Weekday | Holiday/weekend |
|---|---:|---|---|
| Fase 1 | 1.5x | First 60 overtime minutes | 0 minutes |
| Fase 2 | 2x | Remaining overtime minutes | First 480 overtime minutes |
| Fase 3 | 3x | 0 minutes | Next 60 overtime minutes |
| Fase 4 | 4x | 0 minutes | Next 180 overtime minutes |

If holiday overtime exceeds the defined 12-hour window, remaining minutes stay outside displayed phases and are not silently assigned to another multiplier.

## Behavior

- `overtime_phases` always returns four phase entries with labels, rates, minutes, hours, and duration, including zero entries.
- Existing overtime duration remains total actual overtime minutes, not multiplied compensation.
- Overtime baseline remains conditional: early check-in uses standard work start; late check-in uses actual check-in.
- Work duration and pre-start checkout rules remain unchanged.

## Consumers

- Detail report renders four phase groups, each with hours and minutes.
- Summary report aggregates all four phase minute totals per employee.
- Detail and summary Excel exports include four phase hour/minute pairs.
- Existing frontend or API consumers receive an additional phase entry; no phase is removed.

## Testing

- Weekday overtime of 30, 60, and 120 minutes maps to phases 1/2 correctly.
- Holiday overtime of 8 hours maps entirely to phase 2.
- Holiday overtime of 12 hours maps to phase 2 = 8h, phase 3 = 1h, phase 4 = 3h.
- Zero phases are present and rendered as zero.
- Summary aggregation and both export modes include phase 4.
