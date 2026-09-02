# Review results — G0 Clinic foundation (auth, shell, retire teams)

- Date: 2026-09-02
- Mode: verify
- Scope: `app/`, `routes/`, `resources/js/`, `database/`, `tests/Feature/`, `config/`, `.env.example`
- Goal: G0 — Clinic foundation (auth, shell, retire teams)

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | Question | open | `app/Enums/ClinicRole.php`, `architecture.md` §6 / G4 | Receptionist Treatments access: §6 write row vs G4 view-only |
| R2 | High | fixed | `tests/Feature/Auth/AuthenticationTest.php` | Six-role login Done-when not proven via `login.store` |
| R3 | Medium | fixed | `app/Enums/ClinicRole.php`, `resources/js/components/AppSidebar.vue` | Module visibility duplicated in PHP + Vue (drift risk) |
| R4 | Medium | fixed | `tests/Feature/` | No tests for module 403 matrix (§6 direct-URL enforcement) |
| R5 | Medium | fixed | `tests/Feature/StaffTest.php` | Staff authz tests omit nurse/accountant/lab and `staff.create` GET |
| R6 | Medium | fixed | `app/Providers/AppServiceProvider.php` | `StaffPolicy` bound to entire `User` model (narrow policy surface) |
| R7 | Low | open | `app/Http/Controllers/Settings/StaffController.php` | Unused `Request` params on index/create (PHPStan level 7) |
| R8 | Low | open | `app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php` | Orphan 2FA artifacts remain though G0 disables 2FA |
| R9 | Question | open | `app/Http/Controllers/Settings/ProfileController.php` | Sole Admin can self-delete via profile destroy |

## Assessment overview

- Guidelines: Core G0 wiring (team routes/models/tests removed on disk, Fortify registration off, `/dashboard` without prefix, Golden Smile config, session 30, password min 10) largely matches architecture. Team retirement appears complete on disk. **Receptionist → Treatments placeholder** is ambiguous between §6 (write row only) and G4 (view-only) — not a clear G0 violation.
- Blast radius: Low for routing/session changes. **Duplicated module matrix** in PHP + Vue increases drift risk on every future role tweak.
- Security: Admin-only staff create is enforced (`StaffPolicy` + `StoreStaffRequest::authorize()` on POST; controller `authorize()` on index/create). Password min 10 is centralized via `Password::defaults()` and covered in staff/security/reset tests. No registration surface. G0 placeholders expose no mutation endpoints.
- Readability: Staff and placeholder module flows are straightforward. Settings staff UI always shows create form (acceptable while route is Admin-only).
- Extensibility: Mapping `StaffPolicy` to `User::class` reserves the whole model for two staff methods — future `update`/`delete` User checks will silently deny unless policy is split or extended.
- Cohesiveness: Single-clinic role model and Wave 1 shell cohere. Nav visibility should eventually be driven from one source (`ClinicRole`) with view/write split before G4 mutations ship.

## Critical

None.

## High

### R2 — Six-role login Done-when not proven via `login.store`
- Severity: High
- Status: fixed
- Path: `tests/Feature/Auth/AuthenticationTest.php`, `tests/Feature/DashboardTest.php`
- Area: guidelines
- Finding: G0 / T5 Done-when requires each of the six clinic roles to authenticate. Tests prove dashboard access via `actingAs()` for all roles, but only one generic factory user is exercised through `POST login.store`. Role-specific login failures (e.g. bad password per role, missing/invalid role column) would not be caught.
- Evidence: `AuthenticationTest` login tests use `User::factory()->create()` once (default Receptionist) — no `->with([...ClinicRole...])` dataset. `DashboardTest` covers six roles with `actingAs`, not credential login. Architecture G0 verify bullet: "Each of the six roles can log in."
- Suggested fix: Add a Pest dataset test posting to `route('login.store')` with `password12` for each `ClinicRole` factory state (`admin`, `dentist`, `receptionist`, `nurse`, `accountant`, `lab`); assert redirect to `dashboard` and authenticated session.

## Medium

### R3 — Module visibility duplicated in PHP + Vue (drift risk)
- Severity: Medium
- Status: fixed
- Path: `app/Enums/ClinicRole.php`, `resources/js/components/AppSidebar.vue`
- Area: cohesiveness
- Finding: Wave 1 nav authorization is implemented twice: `ClinicRole::canViewModule()` (server) and `canView()` in `AppSidebar.vue` (client). Any change to §6 or G4 view/write rules must be edited in two places; the matrices can drift independently.
- Evidence: Parallel module keys (`dashboard`, `patients`, `appointments`, `treatments`, `billing`, `inventory`, `reports`, `settings`) with separate role arrays in `ClinicRole::canViewModule()` (lines 28–37) and `AppSidebar.vue` `canView()` (lines 39–63).
- Suggested fix: Expose allowed modules from the server (e.g. `auth.user.allowed_modules` in `HandleInertiaRequests` derived from `ClinicRole`) and filter nav items from that single list; remove the duplicated Vue matrix.

### R4 — No tests for module 403 matrix (§6 direct-URL enforcement)
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/`
- Area: guidelines
- Finding: Architecture §6 requires 403 on forbidden visits and sidebar hiding unauthorized modules. `PlaceholderModuleController` enforces `canViewModule()` before render, but no feature tests assert blocked roles receive 403 on direct URLs.
- Evidence: Grep of `tests/` shows no assertions on placeholder routes (`patients`, `billing`, `treatments`, etc.) with role expectations. Only staff 403s are tested in `StaffTest.php`.
- Suggested fix: Add matrix tests with clear §6/G4 expectations — e.g. Accountant `GET /patients` → 403, Lab `GET /billing` → 403, Dentist `GET /patients` → 200. Include at least one denied and one allowed path per non-trivial role.

### R5 — Staff authz tests omit nurse/accountant/lab and `staff.create` GET
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/StaffTest.php`
- Area: guidelines
- Finding: T3/T5 require non-Admin roles blocked from staff-admin routes. Tests cover Receptionist and Dentist on index + store only; Nurse, Accountant, Lab and `GET staff.create` are untested.
- Evidence: `StaffTest.php` has `receptionist cannot view staff index`, `receptionist cannot create staff`, `dentist cannot create staff` — no cases for `User::factory()->nurse()`, `accountant()`, `lab()`, or `route('staff.create')`.
- Suggested fix: Extend dataset to all five non-Admin roles for `staff.index`, `staff.create`, and `staff.store`; expect `403` on each.

### R6 — `StaffPolicy` bound to entire `User` model (narrow policy surface)
- Severity: Medium
- Status: fixed
- Path: `app/Providers/AppServiceProvider.php`, `app/Policies/StaffPolicy.php`
- Area: extensibility
- Finding: `Gate::policy(User::class, StaffPolicy::class)` attaches staff rules to every `User` authorization check. Policy defines only `viewAny` and `create`. Any future `$this->authorize('update', $user)` or `$user->can('delete', $targetUser)` will deny by default, which is easy to misdiagnose.
- Evidence: `AppServiceProvider` line 29 registers `StaffPolicy` on `User::class`. `StaffPolicy` has only `viewAny()` and `create()`. `ProfileController` currently skips policy (works today). Staff routes: index/create call `$this->authorize(...)`; store relies on `StoreStaffRequest::authorize()` — all three paths do fire policy checks for staff operations.
- Suggested fix: Register staff abilities explicitly (e.g. `Gate::define('viewStaff', ...)` / `Gate::define('createStaff', ...)`) or use a dedicated staff resource for the policy target instead of `User::class`. Keep Admin-only enforcement on all three staff routes.

## Low

### R7 — Unused `Request` params on index/create (PHPStan level 7)
- Severity: Low
- Status: open
- Path: `app/Http/Controllers/Settings/StaffController.php`
- Area: readability
- Finding: `index(Request $request)` and `create(Request $request)` never use `$request` after `authorize()` — likely PHPStan level-7 noise in `composer types:check`.
- Evidence: `StaffController` lines 16–40 accept `Request $request` but only call `$this->authorize('viewAny', User::class)` or `$this->authorize('create', User::class)`.
- Suggested fix: Remove unused parameter or reference `$request->user()` explicitly if preferred for clarity.

### R8 — Orphan 2FA artifacts remain though G0 disables 2FA
- Severity: Low
- Status: open
- Path: `app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php`, `app/Http/Responses/TwoFactorLoginResponse.php`, `database/factories/UserFactory.php`
- Area: cohesiveness
- Finding: G0 / architecture explicitly defer 2FA ("do not build in G0–G15"). Starter-kit 2FA request/response classes and `withTwoFactor()` factory state remain, increasing confusion and dead surface.
- Evidence: Files present under `app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php` and `app/Http/Responses/TwoFactorLoginResponse.php`; `UserFactory::withTwoFactor()` at lines 91–97; `config/fortify.php` features array excludes 2FA.
- Suggested fix: Remove unused 2FA classes/states in a follow-up cleanup pass.

## Question

### R1 — Receptionist Treatments access: §6 write row vs G4 view-only
- Severity: Question
- Status: open
- Path: `app/Enums/ClinicRole.php`, `resources/js/components/AppSidebar.vue`, `architecture.md` §6 / G4
- Area: guidelines
- Finding: Initial review flagged Receptionist access to Treatments as a §6 violation. Re-check shows the matrix row is **"Treatments / Rx / chart write"** with Receptionist = `—` (no write), while **G4** explicitly assigns Receptionist **view-only** on treatment history. G0 placeholders are GET-only index pages with no mutations. Allowing Receptionist to see Treatments nav and `GET /treatments` placeholder is **consistent with G4 view intent** and does not expose write capability in G0. §6 does not define a separate "Treatments view" row (unlike Patients/Appointments), so strict "sidebar hides unauthorized modules" reading is ambiguous until view vs write is split.
- Evidence: `architecture.md` §6: Treatments row is write-scoped; Receptionist = `—`; Nurse = `clinical view`. G4 Done-when: "Receptionist view-only". `ClinicRole::canViewModule('treatments')` includes Receptionist. `AppSidebar.vue` includes receptionist for treatments. `PlaceholderModuleController` only serves GET placeholder pages — no POST/write routes in G0.
- Suggested fix: Confirm product intent with team. If Receptionist should see Treatments as view-only from G0: keep current behavior; add a §6 "Treatments view" row or document that G0 `canViewModule` means index/view access and write is gated at G4. If Receptionist should not see Treatments until G4: remove Receptionist from treatments allow-list and hide nav item. Either way, split view vs write in `ClinicRole` before G4 mutations ship.

### R9 — Sole Admin can self-delete via profile destroy
- Severity: Question
- Status: open
- Path: `app/Http/Controllers/Settings/ProfileController.php`, `tests/Feature/Settings/ProfileUpdateTest.php`
- Area: blast-radius
- Finding: Any authenticated user, including the only seeded Admin, can delete their account via `profile.destroy` (password-confirmed). If this is the sole Admin, the clinic has no staff login until re-seed/manual DB fix. Unclear if self-delete is intended for staff-only portal.
- Evidence: `ProfileController::destroy()` deletes `$request->user()` after logout. Test `user can delete their account` passes for generic factory user. `DatabaseSeeder` creates single Admin (`a.santos@goldensmile.clinic`).
- Suggested fix: Confirm product intent. If disallowed, block delete for Admin and/or last remaining Admin; if allowed, document recovery (re-run seeder / manual Admin recreate).

## Verify pass notes

> Fill on verify pass only. Re-check prior findings; confirm `fixed` or leave `open` / `deferred` with residual detail in `progress.md`.

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R2 | fixed | fixed | Dataset `login.store` for all six roles |
| R3 | fixed | fixed | `viewableModules()` + shared `allowed_modules` |
| R4 | fixed | fixed | `PlaceholderModuleTest` 403/200 matrix |
| R5 | fixed | fixed | All five non-Admin roles on staff routes |
| R6 | fixed | fixed | `viewStaff` / `createStaff` gates |
| R1 | open | open | Deferred to Backlog B2 |
| R7 | open | open | Deferred to Backlog B3 |
| R8 | open | open | Deferred to Backlog B4 |
| R9 | open | open | Deferred to Backlog B5 |

Test gate unmet on this host (PHP 8.3 / no Docker). No open Critical/High. T1 team leftovers alleged by verifier were **not** on disk (`tests/Feature/Teams` absent).
