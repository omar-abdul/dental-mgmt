# GOAL — G7 Dashboard

- **Architecture id:** G7
- **Mode:** A-light
- **Why:** read-model KPIs and lists; no money mutations, session, or schema other goals depend on
- **Started:** 2026-09-03
- **Closed:** 2026-09-03
- **Owner run:** g7-dashboard

## Summary

Replace the Overview placeholder with live clinic KPIs (today’s appointments, active patients, unpaid/issued invoices, low-stock items), a Mon–Sun weekly visits chart, a recent `activity_logs` feed, and today’s upcoming appointments table. Counts use `Africa/Mogadishu`. Role-scoped: omit module data the viewer cannot see (Lab limited).

**Closed.** T1–T3 completed. A-light Sail gate 198 passed.

## Scope in

- `DashboardController` + `resources/js/pages/Dashboard.vue`
- Queries against existing Wave 1 tables (no new migrations)
- Pest on factory-seeded counts and role omission
- Reuse inventory Card UI patterns; CSS bars for weekly visits (no new npm packages)

## Scope out

- G8 reports hub, G9 screenshot KPI seed totals (1,284 patients, etc.)
- Writing `activity_logs` from G2–G6 mutations (display existing/factory rows; empty state OK)
- Chart libraries, WebSockets, caching layers
- Changing nav / ClinicRole module matrix except using `canViewModule` to omit props

## Verification (from architecture)

- [x] KPIs: today’s appointments, active patients, unpaid/issued invoices, low-stock items
- [x] Weekly visits Mon–Sun; recent `activity_logs`; today’s upcoming table
- [x] Feature test: factory/seeded counts match cards

## Notes

- Timezone: `config('app.timezone')` (`Africa/Mogadishu`). Freeze time in tests with `travelTo`.
- Vacated appointment statuses (do not count): `cancelled`, `no_show`, `rescheduled`.
- Unpaid/issued invoices: `issued`, `partially_paid`, `overdue` — not draft/paid/cancelled/refunded/written_off.
- Active patients: `PatientStatus::Active`, not trashed.
- Low stock: same derivation as inventory (`quantity > 0` and `quantity <= reorder_level`).
- Lab: dashboard 200 but no patient names, no upcoming table, no invoice/patient/appointment/inventory KPIs they cannot view.
- Header already has a user chip; page still needs Overview + on-page greeting/chip.
