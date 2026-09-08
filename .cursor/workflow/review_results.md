# Review results — G16 Clinic ops polish

- Date: 2026-09-08
- Mode: verify
- Scope: T1–T4 (LocalCombobox, patient cascade soft-delete, staff active/inactive, printable reports); prior R1–R4
- Goal: G16 — Clinic ops polish (Mode A)

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | Critical | fixed | `bootstrap/app.php` | `appendToGroup('auth')` shadows `Authenticate` — guests skip login redirect |
| R2 | Medium | fixed | `tests/Feature/ReportsTest.php` | G16 verify bullet missing: feature test for print button |
| R3 | Low | deferred | `resources/js/pages/reports/Index.vue` | Reports hub print chrome incomplete vs child report pages |
| R4 | Medium | fixed | `tests/Feature/PatientTest.php` | No feature test that deleted patient invoices vanish from billing index |

## Assessment overview

- Guidelines: G16 Done-when for T1–T4 holds on Feature evidence. Mandatory gate `./vendor/bin/sail artisan test --compact` on the listed G16 Feature files: **320 passed**. Architecture print-button feature test (R2) and billing-index omission (R4) are present. R3 Low hub `print:hidden` wrap remains.
- Blast radius: R1 fix restores Laravel `auth` alias on clinic groups (`web`, `auth`, `active`). No `auth` middleware **group**. Fortify `password.confirm` / `logout` stay `auth:web` without `active`; clinic pages still log inactive users out (`AuthenticationTest`). Not a new clinic-data finding.
- Security: Guest `DELETE patients.destroy` is **302 to login**, not 403. `EnsureUserIsActive` passes guests through; redirect is `Illuminate\Auth\Middleware\Authenticate`. Inactive login still rejected at Fortify `authenticateUsing`.
- Readability: no new issues in this pass.
- Extensibility: `['auth', 'active']` on `routes/web.php` and `routes/settings.php` is the durable pattern (do not resurrect `appendToGroup('auth')`).
- Cohesiveness: D7 still honored (invoices/payments soft-deleted, not force-deleted). Hub print chrome (R3) still looser than Wave 1 child reports.

## Critical

### R1 — `appendToGroup('auth')` shadows `Authenticate` — guests skip login redirect
- Severity: Critical
- Status: fixed
- Path: `bootstrap/app.php`
- Area: security
- Finding: `$middleware->appendToGroup('auth', [EnsureUserIsActive::class])` registered a middleware **group** named `auth`. Laravel resolves groups before aliases, so `Route::middleware('auth')` skipped `Authenticate`. Guests hit Gates and got **403** instead of login redirect.
- Evidence (verify): `appendToGroup` gone. Alias `'active' => EnsureUserIsActive::class` only. `patients.destroy` middleware is `["web","auth","active"]`. No `auth` group. Guest destroy + FormAbuse `patients destroy` + HttpPageAccess guests all `assertRedirectToRoute('login')` (passed). `EnsureUserIsActive` redirects only when `$request->user()` is inactive — guests fall through to Authenticate.
- Suggested fix: (applied) Use `Route::middleware(['auth', 'active'])`; do not `appendToGroup('auth')`.

## High

None.

## Medium

### R2 — G16 verify bullet missing: feature test for print button
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/ReportsTest.php`
- Area: guidelines
- Finding: Architecture G16 verify requires a feature test that the report print button is present.
- Evidence (verify): `wave 1 report pages include a print control` covers hub + 7 Wave 1 pages; GET 200 + Vue contains `<ReportPrintButton` and `data-test="print-report-button"`.
- Suggested fix: (applied)

### R4 — No feature test that deleted patient invoices vanish from billing index
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/PatientTest.php`
- Area: guidelines
- Finding: T2 Done-when requires deleted patients disappear from billing indexes.
- Evidence (verify): `deleted patient invoices are omitted from billing index` — after destroy, `GET billing.index` Inertia `invoices.data` omits the invoice; `Invoice::withTrashed()` still exists with `deleted_at`.
- Suggested fix: (applied)

## Low

### R3 — Reports hub print chrome incomplete vs child report pages
- Severity: Low
- Status: deferred
- Path: `resources/js/pages/reports/Index.vue`
- Area: cohesiveness
- Finding: Child report pages wrap Print (and back-link) in `print:hidden`. Hub mounts `<ReportPrintButton />` without that wrapper, so the hub Print control can still appear on paper. Sidebar/header/date-filter already use `print:hidden`.
- Evidence: `resources/js/pages/reports/Index.vue` vs `DailyAppointments.vue` (`<div class="flex items-center gap-3 print:hidden">`).
- Suggested fix: Wrap hub header actions in `print:hidden`. Deferred this run (Low).

## Verify pass notes

> Fill on verify pass only. Re-check prior findings; confirm `fixed` or leave `open` / `deferred` with residual detail in `progress.md`.

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R1 | fixed (Corrector) | fixed | Guest destroy is Authenticate **302 → login**, not Gate 403. Route stack `web, auth, active`. Feature gate 320 passed. |
| R2 | fixed (Corrector) | fixed | Hub + 7 Wave 1 pages asserted in `ReportsTest`. |
| R3 | open | deferred | Low hub `print:hidden`. Residue → workflow Backlog B27 (Expires 2026-10-08). |
| R4 | fixed (Corrector) | fixed | Billing index omits soft-deleted invoice; row still in DB with `deleted_at`. |
