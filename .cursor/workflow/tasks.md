# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Retire starter-kit teams

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | No `{current_team}` route prefix, team models/middleware/UI, or `tests/Feature/Teams/*`; `php artisan route:list --path=dashboard` shows `/dashboard` not `/{current_team}/dashboard`; guests hitting `/dashboard` redirect to `/login` |

**Description:** Remove team tenancy (models, migrations, middleware, Fortify team redirects, Vue switcher/invitations, settings Teams). Dashboard at `/dashboard` behind `auth` only (no `verified`). `/` redirects guests to login.

**Evidence:** `routes/web.php` has `/dashboard` with `auth` only; `/` redirects guest→login. `find app resources/js tests -iname '*team*'` empty. `tests/Feature/Teams/` gone. `php artisan route:list` not executed here (host PHP 8.3 cannot boot PHPUnit 12 autoload).

---

### T2 — Roles, session, brand, password

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | `users.role` stores `admin\|dentist\|receptionist\|nurse\|accountant\|lab`; session lifetime 30; `config('app.timezone')` is `Africa/Mogadishu`; `config('app.name')` default is Golden Smile Dental Clinic; passwords shorter than 10 fail validation |

**Description:** `App\Enums\ClinicRole` (TitleCase keys). Migration adds `role` and drops `current_team_id`. User helpers/policies. Password::defaults min 10 in all envs (demo `password12` must pass). `.env.example` APP_NAME + SESSION_LIFETIME=30. Factory states per role; default password `password12`.

**Evidence:** `ClinicRole` enum; migration `add_role_to_users_table`; `Password::min(10)`; `config/app.php` timezone + name; `.env.example` SESSION_LIFETIME=30; factory `password12` + role states.

---

### T3 — Staff-only auth and Admin staff create

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T2 |
| **Done when** | GET/POST `/register` is 404; Admin can create staff of any role; Receptionist (and other non-Admin) GET/POST staff-admin routes get 403; login/logout/remember-me/forgot-password/change-password still work |

**Description:** Disable Fortify registration and email verification. Admin-only staff index/create (Settings). Authorize via policy. Seeder: one Admin (`a.santos@goldensmile.clinic` / `password12`) so the app is runnable before G9.

**Evidence:** `config/fortify.php` only `resetPasswords`; `StaffController` + `viewStaff`/`createStaff` gates; seeder Admin `a.santos@goldensmile.clinic` / `password12`. Registration tests expect 404.

---

### T4 — Golden Smile login and Wave 1 chrome

| Field | Value |
|-------|-------|
| **id** | T4 |
| **status** | completed |
| **depends_on** | T2 |
| **Done when** | Login is split navy/white Golden Smile layout with Welcome Back, no sign-up CTA, footer listing Admin · Dentist · Receptionist · Nurse · Accountant · Lab; sidebar is navy with GS brand and eight Wave 1 nav items (unauthorized hidden); header shows name + role |

**Description:** Restyle AuthSplitLayout + Login. Navy sidebar, GS mark, teal accent in CSS theme. Wave 1 nav: Dashboard, Patients, Appointments, Treatments, Billing, Inventory, Reports, Settings. Placeholder Inertia pages for unimplemented modules (no domain tables). Share `auth.user.role` (and label) via HandleInertiaRequests.

**Evidence:** `AuthLayout` → `AuthSplitLayout` navy split; Login Welcome Back, no sign-up, six-role footer; navy sidebar + GS mark; eight Wave 1 items filtered by `allowed_modules`; header name + role.

---

### T5 — Pest coverage for G0

| Field | Value |
|-------|-------|
| **id** | T5 |
| **status** | blocked |
| **depends_on** | T1, T2, T3, T4 |
| **Done when** | Team tests gone; `php artisan test --compact` passes for auth, dashboard, staff, and password-min-10; each of six roles can authenticate; Receptionist 403 on staff create; registration disabled |

**Description:** Rewrite AuthenticationTest / DashboardTest; replace RegistrationTest with disabled-registration tests; add StaffTest (admin create + 403s); update password tests to `password12` and assert 9-char rejection. Follow testing-best-practices (feature tests, factories, named assertions).

**Evidence:** Tests written (Authentication, Dashboard, Staff, PlaceholderModule, ClinicConfig, Registration 404, password min 10). Team tests gone. Suite not executed: host PHP 8.3 cannot parse PHPUnit 12; Docker daemon not running for Sail. See workflow `progress.md`.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
