# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Dashboard read model

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Authenticated GET `dashboard` Inertia props include role-allowed KPIs and lists computed in clinic TZ; guests still redirect to login; Lab/Accountant omit modules they cannot view (`ClinicRole::canViewModule`); unauthorized KPI keys are `null` (not `0`) |

**Description:** Extend `DashboardController` (extract a small query class only if the controller would mix many unrelated queries poorly). Definitions:

- **Today’s appointments:** `starts_at` in today `[00:00, next 00:00)` clinic TZ; exclude vacated statuses.
- **Active patients:** `status = active`, not soft-deleted.
- **Unpaid/issued invoices:** status in `issued`, `partially_paid`, `overdue`.
- **Low-stock items:** same as inventory index (`quantity > 0 && quantity <= reorder_level`).
- **Weekly visits:** Mon–Sun of the current week in clinic TZ; per-day counts using the same appointment inclusion as today’s KPI; keys `mon`…`sun`.
- **Recent activity:** latest ~10 `activity_logs`, newest first, eager-load `user`; empty list OK.
- **Upcoming today:** remaining today (`starts_at >= now`, same calendar day, not vacated), order by `starts_at`, limit ~12; eager-load patient + dentist. Omit entirely when the role cannot view appointments.

Use `Gate` only if a policy exists; all six roles may hit `/dashboard`. Do not N+1. Money stays integer cents if shown. No new routes.

**Evidence:** `DashboardMetrics::forUser()` + `DashboardController`. Null KPIs when `canViewModule` is false. Orchestrator Sail gate: 198 passed.

---

### T2 — Dashboard Vue surface

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | `Dashboard.vue` shows Overview heading, on-page user name/role chip, up to four KPI cards (hide null KPIs), Mon–Sun weekly visits (CSS bars, no chart package), recent activity list with empty state, and today’s upcoming table with empty state |

**Description:** Match inventory Card / table patterns (`resources/js/pages/inventory/Index.vue`). Single Vue root. Typed props. Links to appointments/patients/billing/inventory are optional and must respect allowed modules. Do not add npm packages.

**Evidence:** `resources/js/pages/Dashboard.vue` — cards, CSS bars, activity, upcoming; null sections hidden.

---

### T3 — Pest coverage

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T2 |
| **Done when** | `DashboardTest` (or sibling) asserts factory counts match Inertia KPI/list props; vacated/inactive/paid/out-of-stock rows are excluded; Lab omits PHI/finance KPIs; existing guest redirect + six-role 200 tests still pass; `./vendor/bin/sail artisan test --compact` green |

**Description:** `test()` + factories. `travelTo` a fixed `Africa/Mogadishu` instant. Assert known counts, do not reimplement controller math. Cover: today’s count ignores cancelled; weekly bucket is the correct weekday; upcoming excludes past-today and cancelled; activity newest first. Accountant sees unpaid KPI and not appointment KPIs. Nurse sees appointment/patient/stock KPIs and not unpaid invoices.

**Evidence:** `tests/Feature/DashboardTest.php` (16 tests). Orchestrator: `./vendor/bin/sail artisan test --compact` **198 passed** (953 assertions).

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
