# Workflow CHANGELOG

Per-run fix log for goal-workflow Corrector appends. Architecture goal completions go in root `changelog.md`.

## 2026-09-02 — G0 Clinic foundation — correct pass

### Fixed

- **R2** — Added Pest dataset posting `login.store` with `password12` for all six `ClinicRole` factory states; asserts authenticated + redirect to dashboard (`tests/Feature/Auth/AuthenticationTest.php`)
- **R3** — Added `ClinicRole::viewableModules()`, shared `allowed_modules` via `HandleInertiaRequests`, updated `auth.ts` and `AppSidebar.vue` to filter nav from server list (`app/Enums/ClinicRole.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/`)
- **R4** — Added `PlaceholderModuleTest` covering §6 GET 403/200 matrix and guest redirect (`tests/Feature/PlaceholderModuleTest.php`)
- **R5** — Collapsed non-Admin staff 403 tests into datasets for `staff.index`, `staff.create`, and `staff.store` across all five non-Admin roles (`tests/Feature/StaffTest.php`)
- **R6** — Replaced `StaffPolicy` on `User::class` with explicit `viewStaff` / `createStaff` gates; updated controller and form request; removed unused policy class (`app/Providers/AppServiceProvider.php`, `app/Http/Controllers/Settings/StaffController.php`, `app/Http/Requests/Settings/StoreStaffRequest.php`)

### Notes

- Tasks touched: T3, T5 (authz + login verification)
- Tests / checks run: `vendor/bin/pint --dirty --format agent`; `php artisan test` not run on host PHP 8.3 (PHPUnit 12 requires PHP 8.4+)
