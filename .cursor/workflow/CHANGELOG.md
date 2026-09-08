# Workflow CHANGELOG

Per-run fix log for goal-workflow Corrector appends. Architecture goal completions go in root `changelog.md`.

## 2026-09-08 — G16 — correct pass

### Fixed

- **R1** — Removed `appendToGroup('auth')` so the `auth` alias (`Authenticate`) is applied again; inactive staff use the `active` alias on authenticated route groups (`bootstrap/app.php`, `routes/web.php`, `routes/settings.php`)
- **R2** — Feature test that each Wave 1 report page mounts and includes `<ReportPrintButton>` with `data-test="print-report-button"` (`tests/Feature/ReportsTest.php`)
- **R4** — Feature test that a deleted patient’s invoice is omitted from billing index (`tests/Feature/PatientTest.php`)

### Deferred / wontfix

- **R3** — Low; hub `print:hidden` chrome. Not requested this run.

### Notes

- Tasks touched: T2 (guest redirect), T3 (active middleware wiring), T4 (print feature coverage)
- Tests / checks run: `./vendor/bin/sail artisan test --compact tests/Feature/PatientTest.php tests/Feature/ReportsTest.php tests/Feature/HttpPageAccessTest.php tests/Feature/StaffTest.php tests/Feature/Auth/AuthenticationTest.php` — 221 passed. `vendor/bin/pint --dirty --format agent` passed.
