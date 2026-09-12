# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Print stylesheet: full-width light report

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | `@media print` hides `[data-slot="sidebar"]` (gap + panel), header, toaster. `[data-slot="sidebar-inset"]` is full width with overflow visible and height auto. Print uses white background and dark text even if `html.dark`. |

**Description:** Add a print layer in `resources/css/app.css` (do not rely only on `print:hidden` on the inner fixed sidebar). Keep screen layout unchanged.

**Evidence:** `app.css` `@media print`. CDP print media on daily appointments (`html.dark`): sidebar `display:none`, inset overflow `visible`, width ~1904px, bg white, color `#111`. Header `display:none`. Table wrap height 3708 = scrollHeight (not clipped to viewport).

---

### T2 — Hide remaining report print chrome

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Reports hub Print control is in `print:hidden` (same as child reports). Date filters already `print:hidden`. On-screen Print remains visible and clickable. |

**Description:** Close Backlog B27. Child report header actions already wrap Print in `print:hidden`.

**Evidence:** `ReportPrintButton` and hub wrapper `print:hidden`. `AppSidebarHeader` root `print:hidden` (class on layout was previously dropped). Print media: Print button `display:none`; on-screen browser test still `assertPresent('@print-report-button')`. B27 closed.

---

### T3 — Tests and print preview

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T1, T2 |
| **Done when** | Feature test asserts the print CSS contract (sidebar slot hidden, inset overflow visible). Browser: admin opens daily appointments, sees Print, and the hub Print wrapper includes `print:hidden`. `./vendor/bin/sail artisan test --compact tests/Feature/ReportsTest.php tests/Browser/ReportsTest.php` passes after `sail npm run build`. Live print media: no sidebar gap, light page. |

**Description:** Extend existing Reports tests. Drive the UI. Do not open the OS print dialog in Pest.

**Evidence:** `./vendor/bin/sail npm run build`. `vendor/bin/pint --dirty --format agent` passed. `./vendor/bin/sail artisan test --compact tests/Feature/ReportsTest.php tests/Browser/ReportsTest.php` — 35 passed. Live print: hub, daily appointments, payments.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
