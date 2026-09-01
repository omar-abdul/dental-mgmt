# Golden Smile Dental Clinic — Architecture

Single-clinic management information system for Golden Smile Dental Clinic. Staff (Admin, Dentist, Receptionist) authenticate, then run patients, appointments, treatments, billing, inventory, and reports from a Vue/Inertia dashboard.

This file is the source of truth for **what to build** and **when it is done**. Implementation happens only via goal-workflow G-ids below. Do not start G(n+1) until G(n) is completed (or skipped/cancelled with user ack) and Critical findings are closed.

Thesis source: §4.5–4.8 (database, modules, UI, testing). UI direction: attached screenshots (login, dashboard, patient form, appointment calendar, inventory). Canonical demo data: [`database/data/golden-smile.example.json`](database/data/golden-smile.example.json).

---

## 1. Product

**Clinic:** Golden Smile Dental Clinic (Philippines, ₱).
**Users:** clinic staff only. Patients do not log in.
**Roles:** Admin · Dentist · Receptionist (see §6).
**Not a multi-tenant SaaS.** One clinic, one database.

### Modules (thesis → product)

| Thesis module | Product surface | Primary actors |
|---------------|-----------------|----------------|
| User authentication | Login, logout, change password, roles | All staff |
| Patient management | Register, update, search, medical history, delete | Admin, Receptionist (Dentist: view/history) |
| Appointment management | Book, edit, cancel, daily schedule | Receptionist, Admin (Dentist: own column) |
| Treatment management | Diagnosis, procedures, prescriptions, history | Dentist, Admin (Receptionist: view) |
| Billing and payment | Invoices, payments, receipts, balances | Admin, Receptionist |
| Inventory | Items, stock, reorder alerts, stock reports | Admin, Receptionist (Dentist: view) |
| Reports | Patient, appointment, revenue, treatment, inventory | Role-filtered (see §6) |
| Dashboard | KPIs, weekly visits, activity, today’s appointments | All staff |

---

## 2. Stack and constraints

Already in the repo (Laravel Vue starter kit). **Do not add Composer/npm dependencies** without user approval.

| Layer | Choice |
|-------|--------|
| PHP | 8.3+ (project targets 8.5) |
| Laravel | 13 |
| Auth | Laravel Fortify (session), existing settings (profile/password) |
| Front | Inertia.js v3 + Vue 3 + Tailwind CSS 4 + reka-ui / existing `components/ui` |
| Routes | Laravel Wayfinder (`@/actions`, `@/routes`) |
| Tests | Pest feature tests; factories over tinker |
| Money | Integer **centavos** (₱1.00 = 100). Never float. |
| Time | `Asia/Manila`. Store UTC-capable datetimes; display clinic local. |
| DB | Schema must be **MySQL-compatible**. Local/tests may keep SQLite. No SQLite-only types/JSON-path tricks. |
| Locale | English UI; Philippine phone (`09…`) and ₱ formatting. |

**HTTP style:** Inertia pages, not a public JSON API. Form requests + policies + (when non-trivial) action classes.

**Pages live in** `resources/js/pages` (Inertia convention).

---

## 3. Current state

**Shipped:** Laravel Vue starter kit only.

- Fortify login/register/password reset/email verification
- Team tenancy: `{current_team}` URL prefix, team switcher, invitations, `TeamRole` owner/admin/member
- Placeholder dashboard; sidebar is Dashboard + starter-kit footer links
- Auth layout is the simple centered form, not the split Golden Smile login
- `APP_NAME=Laravel`; SQLite default
- No patients, appointments, treatments, invoices, inventory, or clinic roles

**Next:** G0 (clinic foundation). Do not implement domain modules against the team URL prefix.

---

## 4. Decisions

Settled. Implementers follow these; do not re-litigate inside a G-id.

### D1 — Single clinic, retire starter-kit teams

Golden Smile is one clinic. Starter **teams** (slug URLs, invitations, owner/admin/member) conflict with clinic roles and with mock URLs (`/login`, `/dashboard`, `/patients`).

G0 removes team tenancy from the product path: drop `{current_team}` prefix, team switcher, invitations, and team-related Fortify redirects. Keep Fortify session auth, profile, and password change.

Existing `tests/Feature/Teams/*` are deleted or rewritten in G0; they must not keep the product coupled to teams.

### D2 — Clinic roles on `users`, not a permissions UI

`App\Enums\ClinicRole`: `Admin`, `Dentist`, `Receptionist`. Stored as a string column on `users`.

Authorization is Laravel policies + `ClinicRole` helpers. No Spatie/permission package unless later approved.

A user may be **Admin and a practicing dentist** (mock: Dr. A. Santos is Administrator and Room 1). Model that as `role = Admin` plus a `dentists.user_id` row. Receptionists are not dentists.

### D3 — Staff-only accounts

Disable Fortify **registration** and **email verification** as product features. Staff are created by Admin (or the demo seeder). Seeded users are treated as verified.

Login identifier is **email** (Fortify `username` remains `email`). The login field label may read “Username or Email” to match the mock; authenticate on email.

Logout, remember-me, and forgot-password stay. Change password stays in Settings.

### D4 — Dentists and rooms are first-class

`dentists` and `rooms` are tables. Appointments require both. Demo rooms are Room 1–3, each typically assigned to one dentist for the day view, but the schema allows any dentist+room pair so conflicts can be enforced independently (same dentist **or** same room overlapping).

### D5 — Catalog for procedures; treatments are clinical records

`treatment_types` is the catalog (name, calendar color, default duration, default fee). Booking an appointment picks a type. Completing care creates a `treatments` row (diagnosis, procedures, prescriptions) optionally linked to the appointment. Invoices are generated from a treatment (or explicit line items), not invented ad hoc without a patient.

### D6 — Reports are queries, not a `reports` table

Thesis lists “Reports” as a main table. We do **not** persist report documents. Report pages run aggregations. Dashboard “recent activity” uses `activity_logs`.

### D7 — Soft-delete patients; cancel appointments

Patients: `softDeletes`. Appointments: `status = cancelled` (keep the row for audit). Inventory quantity 0 is “Out of Stock”, not a delete.

### D8 — Demo data file is canonical

[`database/data/golden-smile.example.json`](database/data/golden-smile.example.json) is the named-record source (Maria Santos, calendar appointments, three visible inventory SKUs, staff emails). Seeder **generate** rules fill KPI totals (1,284 patients, 86 items, etc.). If the user later supplies another JSON, replace that file and keep the same keys.

### D9 — Visual system

Match screenshots, using existing Vue/Tailwind primitives:

- Navy sidebar, GS mark, “Golden Smile Dental Clinic”
- Teal/green accent; blue primary actions; green success / amber warning / red danger badges
- Login: split layout (navy brand panel + white form)
- Status badges: In Stock / Low Stock / Out of Stock; Confirmed / Pending
- Calendar cards: left color bar from `treatment_types.color`
- Charts: CSS/SVG bars (no new chart library)
- Light dashboard chrome; keep starter dark-mode tokens working but **design against the light mockups**

### D10 — No public patient portal, SMS, or payment gateway

In-clinic recording of cash/GCash/card/PhilHealth. Printable receipt view. No PayMongo/Stripe, no SMS reminders, no patient self-booking in G0–G9.

---

## 5. Domain model

### 5.1 ER (logical)

```
users 1──0..1 dentists
rooms 1──* appointments
dentists 1──* appointments
patients 1──* appointments
patients 1──* treatments
appointments 0..1──0..1 treatments
treatment_types 1──* appointments
treatments 1──* treatment_procedures
treatments 1──* prescriptions
patients 1──* invoices
treatments 0..1──* invoices
invoices 1──* invoice_items
invoices 1──* payments
inventory_items 1──* inventory_movements
users 1──* activity_logs
```

### 5.2 Tables and columns

Use `id` bigints, `timestamps()`, and foreign keys with `restrict`/`cascade` as noted. Money columns: unsigned integer centavos.

**users** (extend existing)

- `name`, `email` unique, `password`, `remember_token`
- `role` string (`admin` \| `dentist` \| `receptionist`)
- Keep Fortify columns that remain enabled; drop `current_team_id` in G0
- Email verified at: nullable; seeder/admin-created users set it

**rooms**

- `name` (e.g. `Room 1`), `sort_order`, `is_active`

**dentists**

- `user_id` unique FK users (required in v1)
- `display_name` (e.g. `Dr. A. Santos`)
- `default_room_id` nullable FK rooms
- `is_active`

**patients**

- `full_name`, `date_of_birth` date, `sex` (`female` \| `male`)
- `contact_number`, `email` nullable, `occupation` nullable
- `home_address` text
- `known_allergies` string (dropdown values; allow `None`)
- `existing_medical_condition` string
- `referred_by` string (e.g. `Walk-in`)
- `insurance_provider` nullable (e.g. `PhilHealth`)
- `emergency_contact_name` nullable, `emergency_contact_number` nullable
- `status` (`active` \| `inactive`), `softDeletes`
- `created_by` FK users

**treatment_types**

- `name` unique, `slug` unique
- `color` (token: `blue` \| `amber` \| `red` \| `teal` \| `green` — maps to calendar card)
- `default_duration_minutes` unsigned
- `default_fee_centavos` unsigned
- `is_active`

**appointments**

- `patient_id`, `dentist_id`, `room_id`, `treatment_type_id` FKs
- `starts_at`, `ends_at` datetimes
- `status` (`pending` \| `confirmed` \| `completed` \| `cancelled` \| `no_show`)
- `notes` nullable, `created_by` FK users
- Indexes: `(dentist_id, starts_at)`, `(room_id, starts_at)`, `(starts_at)`

**Overlap rule (G3):** reject a save when another non-cancelled appointment shares the dentist **or** the room and the time ranges overlap (`starts_at < other.ends_at AND ends_at > other.starts_at`). Enforce in a transaction with `lockForUpdate` on candidate rows.

Clinic day view: 08:00–16:00 Asia/Manila.

**treatments**

- `patient_id`, `dentist_id` required
- `appointment_id` nullable unique (one treatment per appointment)
- `diagnosed_at` datetime, `diagnosis` text, `notes` nullable
- `status` (`draft` \| `completed`)

**treatment_procedures**

- `treatment_id`, `treatment_type_id` nullable (or free-text `name` if custom)
- `tooth_numbers` nullable string, `fee_centavos`, `quantity` default 1

**prescriptions**

- `treatment_id`, `medication`, `dosage`, `instructions`, `prescribed_at`

**invoices**

- `invoice_number` unique (e.g. `INV-2026-0001`)
- `patient_id`, `treatment_id` nullable, `issued_by` FK users
- `issued_at`, `status` (`pending` \| `partial` \| `paid` \| `void`)
- `subtotal_centavos`, `total_centavos`, `amount_paid_centavos`, `balance_centavos`
- Balance = total − sum(payments). Status `paid` iff balance 0; `partial` if 0 < paid < total.

**invoice_items**

- `invoice_id`, `description`, `quantity`, `unit_price_centavos`, `line_total_centavos`

**payments**

- `invoice_id`, `amount_centavos`, `method` (`cash` \| `gcash` \| `card` \| `philhealth`)
- `paid_at`, `received_by` FK users, `reference` nullable
- `receipt_number` unique

**inventory_items**

- `name`, `category` (`ppe` \| `restorative` \| `pharmaceutical` \| `hygiene` \| `consumable`)
- `quantity` unsigned integer, `unit` string (`Box`, `Pcs`, `Roll`, …)
- `reorder_level` unsigned, `unit_cost_centavos` unsigned
- Derived status: `quantity = 0` → out of stock; `quantity > 0 && quantity <= reorder_level` → low; else in stock
- Stock value = sum(`quantity * unit_cost_centavos`)

**inventory_movements**

- `inventory_item_id`, `delta` int (signed), `reason` string, `user_id`, `created_at`
- Updates to `quantity` go through movements so stock history exists

**activity_logs**

- `type` string (e.g. `patient.registered`, `appointment.booked`, `invoice.paid`, `inventory.low`, `treatment.completed`)
- `description`, `subject_type`, `subject_id` nullable, `user_id` nullable, `created_at`
- Written from model events or actions; dashboard reads latest 10

Lookup strings for patient dropdowns (allergies, conditions, referred-by, insurance) live as PHP enums or a small config array in G2 — not extra tables unless lists become admin-editable (out of G0–G9).

---

## 6. Authorization

Unauthenticated users: only `/login`, password reset, `/up`. Everyone else redirects to login.

| Capability | Admin | Dentist | Receptionist |
|------------|:-----:|:-------:|:------------:|
| Dashboard (clinic-wide KPIs) | ✓ | ✓ | ✓ |
| Patients view / search / history | ✓ | ✓ | ✓ |
| Patients create / update / delete | ✓ | — | ✓ |
| Appointments view (all columns) | ✓ | ✓ | ✓ |
| Appointments book / edit / cancel | ✓ | — | ✓ |
| Treatments / prescriptions write | ✓ | ✓ | — |
| Treatments / prescriptions view | ✓ | ✓ | ✓ |
| Invoices generate / record payment / print | ✓ | — | ✓ |
| Invoices view | ✓ | ✓ | ✓ |
| Inventory write | ✓ | — | ✓ |
| Inventory view | ✓ | ✓ | ✓ |
| Reports: patients, appointments, inventory | ✓ | ✓ | ✓ |
| Reports: revenue | ✓ | — | — |
| Reports: treatments (all) | ✓ | own dentist only | — |
| Settings: own profile / password | ✓ | ✓ | ✓ |
| Settings: create/update staff users | ✓ | — | — |

403 on forbidden Inertia visits and on POST/PUT/DELETE. Sidebar hides links the role cannot use (still authorize on the server).

---

## 7. HTTP and UI surface

Named routes, no team prefix after G0. Wayfinder for Vue.

| Route name (indicative) | Path | Page |
|-------------------------|------|------|
| `login` | `/login` | `auth/Login` — split Golden Smile layout |
| `dashboard` | `/dashboard` | `Dashboard` |
| `patients.index` `patients.create` `patients.store` `patients.show` `patients.edit` `patients.update` `patients.destroy` | `/patients`… | `patients/Index`, `patients/Create`, `patients/Show`, `patients/Edit` |
| `appointments.index` + CRUD | `/appointments` | `appointments/Index` day calendar |
| `treatments.index` + create/show | `/treatments` | `treatments/Index`, `treatments/Create`, `treatments/Show` |
| `billing.index` `billing.show` `payments.store` | `/billing` | `billing/Index`, `billing/Show` (+ print) |
| `inventory.index` + CRUD / adjust | `/inventory` | `inventory/Index` |
| `reports.index` + show by type | `/reports` | `reports/Index` |
| `settings.*` | existing | keep profile/security; drop teams UI |

### Screenshot mapping

| Screen | Must show |
|--------|-----------|
| Login | Split navy/white; GS mark; “Golden Smile” / “Dental Clinic”; module bullets; Welcome Back; email + password; Remember me; Forgot password; LOG IN; “Role-based access: Admin · Dentist · Receptionist”; copyright |
| Dashboard | Title “Dashboard Overview”; user chip (name + role + avatar); 4 stat cards (Today’s Appointments, Active Patients, Pending Invoices, Low Stock Items); Weekly Patient Visits bars Mon–Sun; Recent Activity list; Upcoming Appointments — Today table (Time, Patient, Dentist, Procedure, Room, Status) |
| Patient form | Three sections: Basic Personal Information; Medical / Dental History; Emergency Contact. Required markers. Cancel + green “Save Patient Record” |
| Calendar | Day grid 8:00 AM–4:00 PM; columns dentist — room; cards with patient name + treatment type + left color bar |
| Inventory | 4 summary cards; search; + Add Item; table #, Item Name, Category, Quantity, Unit, Reorder Level, Status badges |
| Billing (text only) | Invoice list + detail with itemized charges, totals, record payment, print receipt |

Sidebar (authenticated): Dashboard, Patients, Appointments, Treatments, Billing, Inventory, Reports, Settings. Active item highlighted. Brand: circular GS + “Golden Smile Dental Clinic”. Header right: `Dr. A. Santos` / `Administrator` (from auth user).

---

## 8. Example data

File: [`database/data/golden-smile.example.json`](database/data/golden-smile.example.json).

Named staff:

| Name | Role | Email | Dentist / room |
|------|------|-------|----------------|
| Dr. A. Santos | Admin | `a.santos@goldensmile.clinic` | Yes, Room 1 |
| Dr. R. Lim | Dentist | `r.lim@goldensmile.clinic` | Room 2 |
| Dr. M. Cruz | Dentist | `m.cruz@goldensmile.clinic` | Room 3 |
| Receptionist | Receptionist | `receptionist@goldensmile.clinic` | — |

Demo password for all: `password`.

Named patient **Maria Santos** matches the registration screenshot (DOB 1994-08-14, Female, Penicillin, PhilHealth, spouse Jose Santos, etc.). Calendar/dashboard patients: Juan Dela Cruz, Ana Reyes, Carlo Mendoza, Liza Fernandez, Grace Tan, Noel Garcia, Pedro Alonzo.

KPI **generate** targets (demo seeder, G9): today’s appointments 18, active patients 1,284, pending invoices 7, low stock 3, out of stock 1, inventory items 86, stock value ₱148,200, weekly visits Mon 12 … Sat 25 Sun 18.

Activity feed samples in the JSON (relative timestamps).

---

## 9. Goals

Each goal: **Mode**, **Why**, **Done when** (verify bullets). Orchestrator copies verify bullets into `.cursor/workflow/GOAL.md` when starting that G-id.

---

### G0 — Clinic foundation (auth, shell, retire teams)

- **Mode:** A
- **Why:** session routing, privileged Admin, login contract
- **Depends on:** —

Replace starter-kit teams with a single-clinic staff app. Brand the shell. Staff-only Fortify.

- [ ] `{current_team}` prefix, team switcher, invitations, and team models/middleware are gone from the product path; `php artisan route:list` shows `/dashboard` not `/{current_team}/dashboard`
- [ ] `users.role` is `admin|dentist|receptionist`; unauthenticated users hitting `/dashboard` redirect to `/login`
- [ ] Public registration is disabled; login/logout/remember-me/forgot-password/change-password work
- [ ] Login page is the split Golden Smile layout (brand panel + form); no “sign up” CTA
- [ ] Authenticated chrome: navy sidebar with the eight nav items, GS brand, header user name + role
- [ ] Admin can create another staff user; Dentist/Receptionist cannot
- [ ] Login as each role succeeds; Receptionist visiting a staff-admin-only route gets 403
- [ ] Existing team feature tests are removed or replaced; auth + dashboard tests pass without teams
- [ ] `APP_NAME` / UI brand string is Golden Smile Dental Clinic

**Out of G0:** domain tables, real KPIs (dashboard may be empty states).

---

### G1 — Domain schema and factories

- **Mode:** A
- **Why:** schema every later goal depends on
- **Depends on:** G0

- [ ] Migrations exist for rooms, dentists, patients, treatment_types, appointments, treatments, treatment_procedures, prescriptions, invoices, invoice_items, payments, inventory_items, inventory_movements, activity_logs
- [ ] Money columns are integer centavos; patient `softDeletes`; appointment overlap indexes exist
- [ ] Eloquent models + relationships + factories (+ useful states) exist for each aggregate
- [ ] `php artisan migrate:fresh` succeeds on the default connection
- [ ] Feature test: factory graph can create a patient, appointment, treatment, invoice, payment, and inventory item without manual SQL

**Out of G1:** module UIs (except what G0 already shipped).

---

### G2 — Patient management

- **Mode:** A
- **Why:** medical records + destroy/authorization
- **Depends on:** G1

- [ ] Receptionist/Admin can register a patient with the Figure 5 fields (required: full name, DOB, sex, contact, address); validation errors on missing required
- [ ] Search finds patients by name, email, or contact number
- [ ] Show page includes personal info, medical/dental history, emergency contact, and (empty) treatment history slot
- [ ] Update persists; soft-delete hides the patient from index/search; Dentist cannot create/delete
- [ ] Feature tests cover create/search/update/delete + 403 for Dentist write

---

### G3 — Appointment scheduling

- **Mode:** A
- **Why:** conflict integrity across dentists/rooms
- **Depends on:** G2

- [ ] Day-view calendar: 08:00–16:00, one column per dentist with room label, cards show patient + procedure + type color
- [ ] Receptionist/Admin can book, edit, cancel; overlap on same dentist or same room is rejected (422/validation)
- [ ] Non-overlap in another room/dentist succeeds; cancelled slots do not block
- [ ] Status pending/confirmed visible; dashboard-style table can consume the same query
- [ ] Feature tests: overlap reject, cancel frees slot, role 403 for Dentist mutating

---

### G4 — Treatments and prescriptions

- **Mode:** A
- **Why:** clinical write path + patient-history ownership
- **Depends on:** G3

- [ ] Dentist/Admin can record diagnosis, one or more procedures, and prescriptions against a patient (optionally linked to an appointment)
- [ ] Completing a treatment can mark the linked appointment `completed`
- [ ] Patient show (and treatments index) lists treatment history
- [ ] Receptionist can view but not POST treatments/prescriptions
- [ ] Feature tests: create treatment+rx, history visible, receptionist 403 on write

---

### G5 — Billing and payments

- **Mode:** A
- **Why:** money
- **Depends on:** G4

- [ ] Generate an invoice from a completed treatment (itemized procedures/fees); `invoice_number` unique
- [ ] Record payment(s); `balance_centavos` and status (`pending`/`partial`/`paid`) stay consistent
- [ ] Over-payment rejected; void only by Admin
- [ ] Printable receipt view for a payment; billing index lists recent invoices
- [ ] Feature tests: generate, partial then paid, overpay fail, receptionist can pay, dentist cannot generate

---

### G6 — Inventory

- **Mode:** A-light
- **Why:** stock UX; no payment capture
- **Depends on:** G1

- [ ] Index matches Figure 8: four summary cards, search, add item, table with derived status badges
- [ ] Add/update item; adjust stock via movement (quantity never negative)
- [ ] Low stock and out-of-stock counts match derived rules; stock value = Σ qty × unit cost
- [ ] Dentist can view, not write; Admin/Receptionist can write
- [ ] Feature tests: status derivation, adjust, search, 403

May ship in parallel with G2–G5 after G1 (still do not start G7 until G2–G6 are done).

---

### G7 — Administrator dashboard

- **Mode:** A-light
- **Why:** read-model over existing modules; unchanged write contracts
- **Depends on:** G2, G3, G4, G5, G6

- [ ] Four KPI cards: today’s appointment count, active patients, pending invoices, low-stock items
- [ ] Weekly Patient Visits bar chart (Mon–Sun counts of appointments that day)
- [ ] Recent Activity shows latest `activity_logs` with relative time
- [ ] Upcoming Appointments — Today table with Confirmed/Pending badges and View All → appointments
- [ ] Feature test: seeded/factory data produces the expected card counts

---

### G8 — Reports

- **Mode:** A-light
- **Why:** read-only aggregations
- **Depends on:** G7

- [ ] Report hub with: patients, appointments, revenue (Admin), treatments, inventory
- [ ] Each report is filterable by date range; revenue totals match invoice payments in range
- [ ] Role matrix in §6 enforced (Dentist treatment report scoped to self; Receptionist no revenue)
- [ ] Feature tests per report type + 403 cases

---

### G9 — Demo seed and screenshot parity

- **Mode:** A-light
- **Why:** seeder/docs polish
- **Depends on:** G8

- [ ] `php artisan db:seed` loads `golden-smile.example.json` named records + generate rules
- [ ] Staff can log in with the four demo emails / `password`
- [ ] Named Maria Santos, the calendar appointments, and the three inventory SKUs match the JSON
- [ ] After seed, dashboard KPIs match the generate targets in the JSON (`kpis` object)
- [ ] Activity feed includes the five screenshot events (wording equivalent)
- [ ] Pest uses factories, not the full 1,284-patient seed, for speed

---

## 10. Sequencing and modes

```
G0 (A) → G1 (A) → G2 (A) → G3 (A) → G4 (A) → G5 (A) → G7 (A-light) → G8 (A-light) → G9 (A-light)
                 ↘ G6 (A-light) ↗
```

G6 may run after G1 in parallel with G2–G5. G7 waits for G2–G6.

**Default mode rule:** auth/session, ownership of records, money → A. Shell/seed/reports/dashboard read models → A-light unless a Critical risk appears.

**Testing:** every G-id adds/updates Pest tests for the behavior it ships. Run the narrowest `php artisan test --compact` filter, then ask the user for the full suite.

**Frontend verification:** any UI G-id is not done until the happy path is exercised in the browser (or documented substitute if browser tools are unavailable).

---

## 11. Out of scope (G0–G9)

- Patient-facing portal, online booking, email/SMS reminders
- Payment gateways, BIR official receipts, multi-clinic / franchise tenancy
- Imaging / odontogram / tooth chart UI
- Re-introducing starter-kit teams
- New Composer or npm packages (including chart libraries) without approval
- Mobile apps
- Git commit/PR unless the user asks in the goal

---

## 12. Open questions

Resolved for v1 unless the user overrides:

| Topic | Resolution |
|-------|------------|
| Missing user JSON | Use `database/data/golden-smile.example.json`; replace later if a file is provided |
| Figure 7 billing screenshot missing | Invoice list + detail as in D9/G5 |
| Login “username” | Email only |
| 1,284 patients | Seeder generate; tests use factories |
| Dr. Santos as Admin + dentist | D2 |
| Teams | D1 — remove |
