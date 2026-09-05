# Golden Smile — Architecture

Single-clinic staff app: Golden Smile Dental Clinic (UI) running a DCMS-shaped
domain (JSON contract). Staff authenticate, then run patients, appointments,
clinical work, billing, inventory, and reports from a Vue/Inertia dashboard.

This file is the source of truth for **what to build** and **when it is done**.
Implementation happens only via goal-workflow G-ids. Do not start G(n+1) until
G(n) is completed (or skipped/cancelled with user ack) and Critical findings are
closed.

| Source                                                                               | Wins for                                                                                         |
| ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------ |
| `[database/data/dcms.json](database/data/dcms.json)`                                 | Domain: entities, statuses, roles, fees, working hours, business rules, ID formats, mobile money |
| Thesis §4.5–4.8 + screenshots                                                        | UX of login, dashboard, patient form, day calendar, inventory (and inferred billing)             |
| `[database/data/golden-smile.example.json](database/data/golden-smile.example.json)` | Named demo people/SKUs/KPI generate rules, **reshaped to DCMS fields**                           |

---

## 1. Product

**Brand:** Golden Smile Dental Clinic (screenshots). `settings.clinic.name` is
editable later; default brand string is Golden Smile. **Ops locale:**
`Africa/Mogadishu`, currency **USD**, languages English (UI in G0–G15).
Somali/Arabic copy is out of Wave 1–2. **Users:** clinic staff only. Patients do
not log in. No patient portal in G0–G15. **Roles:** Admin, Dentist,
Receptionist, Dental Nurse, Accountant, Laboratory Staff (DCMS `roles`). **Not
multi-tenant.** One clinic, one database. JSON `branches` stay unused.

### Waves

| Wave                    | Goals   | Meaning                                                                                                                    |
| ----------------------- | ------- | -------------------------------------------------------------------------------------------------------------------------- |
| **1 — Operable clinic** | G0–G9   | Screenshot modules + DCMS core rules (chairs, fees, USD, mobile-money _recording_, 6 roles)                                |
| **2 — DCMS depth**      | G10–G15 | Odontogram/sign-off, lab, batches/POs, expenses/cash close/insurance/payment plans, notification templates, imaging orders |

JSON `required_workflows` that need a provider (live SMS, ZAAD API, WhatsApp)
are **recorded in-app** in Wave 1–2; they do not call external APIs until a
later approved goal.

---

## 2. Stack and constraints

Already in the repo (Laravel Vue starter kit). **Do not add Composer/npm
dependencies** without user approval. Approved for this app: `pestphp/pest-plugin-browser`
and the `playwright` npm package (staff UI E2E).

| Layer   | Choice                                                                                          |
| ------- | ----------------------------------------------------------------------------------------------- |
| PHP     | 8.3+ (project targets 8.5)                                                                      |
| Laravel | 13                                                                                              |
| Auth    | Fortify session; staff-only                                                                     |
| Front   | Inertia v3 + Vue 3 + Tailwind 4 + existing `components/ui`                                      |
| Routes  | Wayfinder (`@/actions`, `@/routes`)                                                             |
| Tests   | Pest HTTP feature tests **and** Pest Playwright browser tests (`tests/Browser`)                  |
| Money   | Integer **USD cents** (`$1.00 = 100`). Never float. JSON decimals map 20 → `2000`               |
| Time    | `Africa/Mogadishu`. Store timezone-aware datetimes.                                             |
| Dates   | `YYYY-MM-DD` in APIs/DB; UI may format for display                                              |
| DB      | MySQL-compatible. Local/tests may use SQLite.                                                   |
| HTTP    | **Inertia pages**, not `/api/v1`. JSON `api_contract` is deferred (see §11).                    |
| IDs     | DCMS formats: `PAT-{YYYY}-{#####}`, `APT-…`, `INV-…`, `RCT-…`, `PAY-…`, `RX-…`, `LAB-…`, `PO-…` |

**Pages** live in `resources/js/pages`.

**Password:** minimum length **10**
(`settings.privacy.minimum_password_length`). Demo password is `password12`.
**Idle:** 30-minute session lifetime matching `automatic_logout_minutes` (G0).

---

## 3. Current state

**Shipped:** G0–G15 (clinic foundation through imaging orders) and Pest Playwright browser coverage for those pages.

- Single-clinic Fortify session (no `{current_team}` / teams)
- Six `ClinicRole` values on `users.role`; public registration off
- Admin-only staff create; password min 10; session 30 minutes; Dentist role also provisions an active `dentists` profile for calendar/dropdowns
- Brand Golden Smile Dental Clinic; timezone `Africa/Mogadishu`
- Split login + navy Wave 1 chrome with a live Reports hub (seven Wave 1 reports)
- Wave 1 tables, models, factories; DCMS fee catalog + working hours seeded; integer cents
- Patients: register/search/show/update/archive; unique `PAT-{YYYY}-{#####}`; audit on show
- Appointments: day calendar from `working_hours`; book/edit/cancel/reschedule/check-in; dentist/chair overlap 422; Friday/outside-hours 422; patient typeahead on book/edit
- Treatments: diagnosis, fee-item procedures, Rx (`RX-…`); complete may set appointment `completed`; Nurse/Receptionist view-only; patient typeahead on create; receptionist can generate an invoice from a completed treatment show
- Billing: invoice from completed treatment; partial/overpay rules; recorded ZAAD/Sahal/eDahab/MyCash; receipts; Admin/Accountant refunds; Dentist view-only; expenses, daily cash close, payment plans, insurance claim stub, MM recon (Admin/Accountant)
- Inventory: four cards, search, add, adjust (Admin confirmation); batches + expiry; PO receive; suppliers; expiry alerts; no negative qty; expired consume blocked
- Dashboard: four role-scoped KPI cards, Mon–Sun weekly visits, recent activity, upcoming today; Lab limited
- Browser tests (`tests/Browser`, Pest + Playwright): login, dashboard, patients, appointments, treatments, billing, inventory (incl. PO receive / expired consume), staff (incl. dentist appearing on the calendar after staff create), reports, chart/encounters, lab orders, notification templates, imaging orders, Golden Smile named-seed login, nav/UX, plus a full-page smoke (`StaffPagesSmokeTest`) and form-abuse smoke (`FormAbuseSmokeTest`); HTTP feature tests still own 403/validation/abuse matrices (`HttpPageAccessTest`, `FormAbuseTest`, `AuthAbuseTest`, `ModelCatalogTest`, `PickerOptionsTest`)

- Reports: hub + daily appointments, patient registration, outstanding balances, payments (method breakdown), inventory stock, low stock, treatment statistics; date range; Admin/Accountant revenue; Dentist self-scoped clinical stats; Lab no finance
- Demo seed: named Golden Smile staff/patients (incl. Ahmed Ali `PAT-2026-00001`, Maria Santos), appointments, three SKUs, FEE catalog; generate extras match JSON `kpis` (1,284 active patients, 18 today appts, 7 unpaid invoices, inventory 86 / low 3 / out 1 / $1,482.00). Pest uses named-only seeder. Weekly `fri: 0` holds when seed day is not Friday.
- Chart: encounter per completed treatment (`ENC-…`); SOAP draft/sign/amendment lock; FDI odontogram + tooth history; treatment plans with item acceptance; Admin/Dentist write; Nurse view; Receptionist 403
- Lab: `LAB-…` orders with DCMS statuses; Admin/Dentist/Lab CRUD; Receptionist 403; no invoice from lab orders
- Notifications: DCMS templates (Admin edit); hourly `appointments:queue-reminders` writes `notification.would_send` audit at 48/24/2 hours; no SMS provider
- Imaging: `IMG-…` orders with result metadata and optional file on the Laravel disk; Admin/Dentist write; Nurse view; Receptionist 403; no DICOM viewer

**Next:** none. Architecture G0–G15 are complete.

---

## 4. Decisions

Settled. Implementers follow these; do not re-litigate inside a G-id.

### D1 — Single clinic, retire starter-kit teams

Drop `{current_team}`, team switcher, invitations, team Fortify redirects. Keep
Fortify session, profile, password change. Delete or rewrite
`tests/Feature/Teams/*` in G0.

### D2 — Six clinic roles on `users`

`App\Enums\ClinicRole`: `Admin`, `Dentist`, `Receptionist`, `Nurse`,
`Accountant`, `Lab`.

Policies + role helpers. No Spatie. Admin who practices: `role = Admin` plus a
`dentists.user_id` row (screenshot: Dr. A. Santos).

JSON permission slugs map to policies (see §6). Admin is `*`.

### D3 — Staff-only accounts

Disable Fortify registration and email verification as product features. Admin
(or seeder) creates staff. Login identifier is **email**. Label may say
“Username or Email”.

### D4 — Rooms contain chairs; bookings conflict on dentist **or** chair

`rooms` 1—* `chairs`. Appointments require `dentist_id` + `chair_id` (+
`starts_at`/`ends_at`). Overlap: same dentist **or** same chair, excluding
`cancelled` / `no_show` / `rescheduled` vacated slots.

Calendar columns may still read `Dr. A. Santos — Room 1` (chair’s room),
matching the screenshot.

### D5 — Fee catalog is the procedure/price source

`fee_items` from DCMS (`CONSULT`, `EXAM`, `CLEAN`, `FILL`, `RCT`, `EXT`,
`CROWN`, `IMPLANT`, `XRAY`). Appointments and invoice lines reference
`fee_item_id`. Calendar color is a column on `fee_items` (not in JSON; we add it
for the screenshot cards).

### D6 — Reports are queries

No `reports` table of documents. `activity_logs` for dashboard; `audit_logs` /
`data_access_logs` for privacy (G2+).

### D7 — Soft-delete / archive; money never physically deleted

Patients: `status` active/inactive/archived + `softDeletes`. Archived =
read-only. Appointments keep rows (`cancelled`, `rescheduled`).
Invoices/payments: `cancelled`/`refunded`/`written_off`, never hard-deleted.

### D8 — Two JSON files

| File                                      | Role                                                                         |
| ----------------------------------------- | ---------------------------------------------------------------------------- |
| `database/data/dcms.json`                 | Contract: schema shape, fees, hours, rules, example Ahmed Ali / ZAAD payment |
| `database/data/golden-smile.example.json` | G9 UI fixture: screenshot names, KPI generate, **DCMS field shapes**         |

### D9 — Visual system

Screenshots: navy sidebar, GS mark, teal accent, split login, status badges,
calendar left color bar, CSS/SVG bars (no new chart library). Display money as
`$`. Login footer lists all six roles.

### D10 — Working hours from DCMS

Sat–Wed 08:00–18:00; Thursday 08:00–13:00; **Friday closed**. Reject bookings
outside hours. Screenshot 08:00–16:00 is **not** the rule.

### D11 — Integer cents vs JSON decimal

JSON `data_quality.money_storage = decimal`. Laravel stores **integer cents**.
Forms/UI show 2 decimal places. Totals must reconcile exactly (integer math).

### D12 — Mobile money is recorded, not gateway-integrated (Wave 1)

Methods: `cash`, `card`, `bank_transfer`, `zaad`, `sahal`, `edahab`, `mycash`,
`insurance`. For ZAAD/Sahal/eDahab/MyCash: require payer phone, transaction_id,
reference; unique `transaction_id`; `verification_status`
(`verification_required` `verified` `failed`); completed ≠ “reference typed”. No
provider API keys on payment rows. Live Telesom/Golis/Somtel APIs are out of
G0–G15.

### D13 — Inertia, not `/api/v1`

JSON `api_contract` documents a future API. Wave 1–2 is the Inertia app. Do not
add a parallel REST surface in these goals.

### D14 — Global row metadata

New domain tables include `created_by`, `updated_by` (nullable FK users) plus
timestamps. Soft delete where DCMS says so.

---

## 5. Domain model (Wave 1 core)

Wave 2 tables are listed under G10–G15; do not create them in G1.

### 5.1 ER (Wave 1)

```
users 1──0..1 dentists
rooms 1──* chairs
chairs 1──* appointments
dentists 1──* appointments
patients 1──* emergency_contacts
patients 1──* patient_allergies
patients 1──* patient_conditions
patients 1──* patient_medications
patients 1──* appointments
fee_items 1──* appointments
patients 1──* treatments
appointments 0..1──0..1 treatments
treatments 1──* treatment_procedures
treatments 1──* prescriptions
prescriptions 1──* prescription_items
fee_items 1──* invoice_items
patients 1──* invoices
invoices 1──* invoice_items
invoices 1──* payments
payments 0..1──0..1 mobile_money_transactions
inventory_items 1──* inventory_movements
users 1──* activity_logs
users 1──* audit_logs
```

### 5.2 Tables

**users** — extend existing: `role`
(`admindentistreceptionistnurseaccountantlab`). Drop `current_team_id` in G0.

**dentists** — `user_id` unique FK, `display_name`, `default_chair_id` nullable,
`is_active`.

**rooms** — `name`, `sort_order`, `is_active`.

**chairs** — `room_id`, `name` (e.g. `Chair 1`), `code` unique (`CHAIR-001`),
`is_active`.

**working_hours** — `weekday` (0=Sunday… or ISO), `opens_at` time nullable,
`closes_at` time nullable. Seed from DCMS (Friday both null).

**patients** — `patient_number` unique (`P000001` or `PAT-2026-00001` — use DCMS
`id_formats.patient` as public id; `patient_number` can equal that string),
`first_name`, `last_name`, `date_of_birth`, `gender` (DCMS options), `phone`
required, `email` nullable, `occupation` nullable, `address` nullable,
`referred_by` nullable, `insurance_provider` nullable, `status`
(active/inactive/archived), `softDeletes`, `created_by`. Display name =
`first_name + last_name`.

**emergency_contacts** — `patient_id`, `name`, `relationship` nullable, `phone`.

**patient_allergies** / **patient_conditions** / **patient_medications** —
`patient_id`, `label`, `is_critical` bool (alerts).

**fee_items** — `code` unique, `name`, `category`, `unit`, `price_cents`,
`tax_rate_bps` (0), `calendar_color`, `default_duration_minutes`, `is_active`.
Seed FEE-001–009.

**appointments** — public `number` (`APT-…`), `patient_id`, `dentist_id`,
`chair_id`, `fee_item_id` nullable, `starts_at`, `ends_at`, `status` (DCMS
settings list; treat `in_treatment` as alias of `in_progress` if it appears),
`reason` nullable, `notes` nullable, `created_by`. Indexes
`(dentist_id, starts_at)`, `(chair_id, starts_at)`. Reschedule updates times and
sets status; keep previous times in `appointment_revisions` (simple:
`cancelled_at`/`previous_starts_at` JSON or a small history table in G3).

**Overlap:** `starts_at < other.ends_at AND ends_at > other.starts_at` on same
dentist or chair, inside a transaction with `lockForUpdate`.

**treatments** — `patient_id`, `dentist_id`, `appointment_id` nullable unique,
`diagnosed_at`, `diagnosis` text, `status`
(`plannedin_progresscompletedcancelled`), `notes`.

**treatment_procedures** — `treatment_id`, `fee_item_id`, `tooth_fdi` nullable,
`quantity`, `fee_cents`.

**prescriptions** — `number` (`RX-…`), `treatment_id`, `patient_id`,
`prescriber_id` (users), `prescribed_at`.

**prescription_items** — `prescription_id`, `medication`, `dosage`,
`instructions`.

**invoices** — `invoice_number` unique, `patient_id`, `treatment_id` nullable,
`issued_by`, `issued_at`, `status`
(draft/issued/partially_paid/paid/overdue/cancelled/refunded), `subtotal_cents`,
`discount_cents`, `tax_cents`, `total_cents`, `amount_paid_cents`,
`balance_cents`. No overpay. Financial rows not hard-deleted.

**invoice_items** — `fee_item_id` nullable, `description`, `quantity`,
`unit_price_cents`, `discount_cents`, `tax_cents`, `line_total_cents`.

**payments** — `payment_number` unique, `invoice_id`, `patient_id`,
`amount_cents`, `method`
(cash|card|bank_transfer|zaad|sahal|edahab|mycash|insurance), `status`
(pending|completed|failed|cancelled|refunded|…), `paid_at`, `received_by`,
`reference_number` nullable. Card/bank/mobile require reference.

**mobile_money_transactions** — 1:1 with mobile-money payments: `provider`
(Telesom/Golis/Somtel/Somlink), `payer_phone`, `transaction_id` unique,
`reference_number`, `verification_status`, `verified_by`, `verified_at`. Never
store API keys.

**receipts** — `receipt_number` unique (`RCT-…`), `payment_id` unique, printable
view.

**inventory_items** — `name`, `category` (Dental Materials, Medicines,
Instruments, PPE, Consumables, Office Supplies), `quantity`, `unit`,
`reorder_level`, `unit_cost_cents`. No negative stock. Derived status: 0 out; ≤
reorder low; else in stock.

**inventory_movements** — `delta`, `type`
(`adjustment_inadjustment_outconsumption`… subset in G6), `user_id`, `reason`.

**activity_logs** — dashboard feed.

**audit_logs** — `action`, `auditable_type/id`, `user_id`, `meta` json, `ip`.
Patient show/index counts as access (G2).

Lookups (allergies, referred-by) stay PHP enums/config in Wave 1.

---

## 6. Authorization

Guests: `/login`, password reset, `/up` only.

| Capability                            | Admin | Dentist | Receptionist | Nurse         | Accountant | Lab         |
| ------------------------------------- | ----- | ------- | ------------ | ------------- | ---------- | ----------- |
| Dashboard                             | ✓     | ✓       | ✓            | ✓             | ✓          | ✓ (limited) |
| Patients view                         | ✓     | ✓       | ✓            | ✓             | —          | —           |
| Patients write / archive              | ✓     | —       | ✓            | —             | —          | —           |
| Appointments view                     | ✓     | ✓       | ✓            | ✓             | —          | —           |
| Appointments book / cancel / check-in | ✓     | —       | ✓            | check-in only | —          | —           |
| Treatments / Rx / chart write         | ✓     | ✓       | —            | clinical view | —          | —           |
| Invoices view                         | ✓     | ✓       | ✓            | —             | ✓          | —           |
| Invoices generate / pay               | ✓     | —       | ✓            | —             | ✓          | —           |
| Refunds / discounts authorize         | ✓     | —       | —            | —             | ✓          | —           |
| Inventory view                        | ✓     | ✓       | ✓            | ✓             | —          | —           |
| Inventory write (G6)                  | ✓     | —       | ✓            | ✓             | —          | —           |
| Expenses (G13)                        | ✓     | —       | —            | —             | ✓          | —           |
| Lab orders (G11)                      | ✓     | ✓       | —            | —             | —          | ✓           |
| Reports: ops                          | ✓     | scoped  | ✓            | ✓             | ✓          | lab         |
| Reports: revenue                      | ✓     | —       | —            | —             | ✓          | —           |
| Staff users                           | ✓     | —       | —            | —             | —          | —           |
| Own profile / password                | ✓     | ✓       | ✓            | ✓             | ✓          | ✓           |

403 on forbidden visits and mutations. Sidebar hides unauthorized modules.

---

## 7. HTTP and UI surface

No team prefix after G0.

Wave 1 nav: Dashboard, Patients, Appointments, Treatments, Billing, Inventory,
Reports, Settings.

Wave 2 adds: Chart, Lab, Imaging, Expenses when those G-ids ship.

| Screen       | Must show (adapted to DCMS)                                                                                                                                                                 |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Login        | Split navy/white; GS brand; Welcome Back; email + password; Remember me; Forgot password; LOG IN; roles Admin · Dentist · Receptionist · Nurse · Accountant · Lab; copyright year = current |
| Dashboard    | Overview; user chip; 4 KPI cards; weekly visits; recent activity; upcoming today                                                                                                            |
| Patient form | First name*, Last name*, DOB*, gender*, phone*; address/occupation/email optional; medical history + emergency contact; Cancel + Save                                                       |
| Calendar     | Day grid clinic hours (not Friday); columns dentist — room; cards name + fee/procedure + color; statuses scheduled/confirmed/…                                                              |
| Inventory    | 4 summary cards; search; add; table + badges                                                                                                                                                |
| Billing      | List + detail, fee lines, totals, pay (incl. mobile money fields), print receipt                                                                                                            |

---

## 8. Example data

**Contract examples** (Ahmed Ali, INV-2026-00001, ZAAD/Sahal/eDahab/MyCash
samples): `dcms.json` → `example_records`.

**UI demo** (`golden-smile.example.json`): Maria Santos et al., three inventory
SKUs, KPI generate, six staff, chairs CHAIR-001–003, fee catalog, demo password
`password12`.

Staff:

| Name          | Role                                  | Email                             |
| ------------- | ------------------------------------- | --------------------------------- |
| Dr. A. Santos | Admin (+ dentist, CHAIR-001 / Room 1) | `a.santos@goldensmile.clinic`     |
| Dr. R. Lim    | Dentist, Room 2                       | `r.lim@goldensmile.clinic`        |
| Dr. M. Cruz   | Dentist, Room 3                       | `m.cruz@goldensmile.clinic`       |
| Receptionist  | Receptionist                          | `receptionist@goldensmile.clinic` |
| Dental Nurse  | Nurse                                 | `nurse@goldensmile.clinic`        |
| Accountant    | Accountant                            | `accountant@goldensmile.clinic`   |
| Lab staff     | Lab                                   | `lab@goldensmile.clinic`          |

KPI generate (G9): today’s appointments 18, active patients 1,284,
pending/issued invoices 7, low stock 3, out of stock 1, items 86, stock value
**$1,482.00** (`148200` cents — USD, not ₱148,200), weekly visits Mon 12 … Thu
22, **Fri 0** (clinic closed), Sat 25, Sun 18.

Also seed Ahmed Ali (`PAT-2026-00001`) from DCMS examples.

---

## 9. Goals

### G0 — Clinic foundation (auth, shell, retire teams)

- **Mode:** A
- **Why:** session routing, privileged Admin, six roles
- **Depends on:** —

- [x] `{current_team}` prefix, team switcher, invitations, and team
      models/middleware are gone; `route:list` shows `/dashboard` not
      `/{current_team}/dashboard`
- [x] `users.role` is `admin\|dentist\|receptionist\|nurse\|accountant\|lab`;
      guests hitting `/dashboard` redirect to `/login`
- [x] Public registration disabled;
      login/logout/remember-me/forgot-password/change-password work; password
      min length 10
- [x] Session lifetime 30 minutes
- [x] Login is split Golden Smile layout; no sign-up CTA; footer lists six roles
- [x] Chrome: navy sidebar (Wave 1 eight items), GS brand, header name + role
- [x] Admin can create staff of any role; other roles cannot
- [x] Each of the six roles can log in; Receptionist hitting staff-admin-only
      gets 403
- [x] Team feature tests removed/replaced; auth + dashboard tests pass without
      teams
- [x] `APP_NAME` / UI brand is Golden Smile Dental Clinic; app timezone
      `Africa/Mogadishu`

**Out of G0:** domain tables, real KPIs.

---

### G1 — Domain schema and factories (Wave 1)

- **Mode:** A
- **Why:** schema later goals depend on
- **Depends on:** G0

- [x] Migrations for rooms, chairs, dentists, working_hours, patients,
      emergency_contacts, patient_allergies, patient_conditions,
      patient_medications, fee_items, appointments, treatments,
      treatment_procedures, prescriptions, prescription_items, invoices,
      invoice_items, payments, mobile_money_transactions, receipts,
      inventory_items, inventory_movements, activity_logs, audit_logs
- [x] Money is integer cents; patient softDeletes; appointment overlap indexes;
      public number columns use DCMS formats
- [x] Models + relationships + factories (+ states) for each aggregate;
      fee_items seeded from DCMS FEE-001–009
- [x] `php artisan migrate:fresh` succeeds
- [x] Feature test: factory graph creates patient, chair appointment, treatment,
      invoice, ZAAD payment + mobile_money_transaction, inventory item

**Out of G1:** Wave 2 tables (odontogram, lab, batches, expenses, …).

---

### G2 — Patient management

- **Mode:** A
- **Why:** medical records, archive, access audit
- **Depends on:** G1

- [x] Receptionist/Admin register with required first_name, last_name,
      date_of_birth, phone; unique patient_number assigned
- [x] Search by name, patient_number, phone, email
- [x] Show: identity, allergies/conditions/meds, emergency contacts, treatment
      history slot; viewing writes an audit/access log
- [x] Update; archive (read-only); Dentist/Nurse cannot create/archive;
      Accountant 403 on patients
- [x] Duplicate warning when first+last+DOB match an existing patient
- [x] Feature tests: create/search/update/archive + 403s + audit row on show

---

### G3 — Appointment scheduling

- **Mode:** A
- **Why:** dentist + chair conflict, hours
- **Depends on:** G2

- [x] Day calendar uses working_hours (Fri empty; Thu ends 13:00; else
      08:00–18:00); columns dentist — room; cards patient + fee name + color
- [x] Receptionist/Admin book/edit/cancel/reschedule/check-in; overlap on
      dentist or chair → 422; cancelled does not block
- [x] Booking outside hours or on Friday → 422
- [x] Statuses from DCMS settings; check-in allowed for Nurse
- [x] Feature tests: overlap, Friday reject, cancel frees slot, 403 for Dentist
      mutating

---

### G4 — Treatments and prescriptions

- **Mode:** A
- **Why:** clinical write path
- **Depends on:** G3

- [x] Dentist/Admin record diagnosis, procedures (fee_items), prescription +
      items; prescriber is the user
- [x] Completing treatment may set appointment `completed`
- [x] Patient show lists history; Nurse can view, not POST Rx; Receptionist
      view-only
- [x] Critical allergy flags visible on the treatment form
- [x] Feature tests: create treatment+rx, history, 403s

---

### G5 — Billing and payments

- **Mode:** A
- **Why:** money + mobile-money recording rules
- **Depends on:** G4

- [x] Invoice from completed treatment/fee lines; `invoice_number` unique;
      totals reconcile in cents
- [x] Partial pay allowed; overpay rejected; statuses issued/partially_paid/paid
- [x] Cash: no extra refs. Card/bank: reference required.
      ZAAD/Sahal/eDahab/MyCash: phone + transaction_id + reference; duplicate
      transaction_id 422; `verification_status` required (cannot complete on
      reference alone)
- [x] Completed payment creates receipt (`RCT-…`); printable view; billing index
- [x] Refunds (Admin/Accountant) reference original payment; invoices not
      hard-deleted
- [x] Dentist can view invoices, not generate/pay; Lab 403 billing
- [x] Feature tests: generate, partial, overpay fail, duplicate ZAAD txn,
      receptionist pay, dentist 403 generate

---

### G6 — Inventory (basic)

- **Mode:** A-light
- **Why:** stock UX; no payment capture
- **Depends on:** G1

- [x] Index matches inventory screenshot: four cards, search, add, table,
      derived badges
- [x] Categories are DCMS inventory categories; quantity never negative;
      movements recorded
- [x] Nurse/Admin/Receptionist write; Dentist view; Accountant 403
- [x] Feature tests: status derivation, adjust, search, 403

May run after G1 in parallel with G2–G5. G7 still waits for G2–G6.

---

### G7 — Dashboard

- **Mode:** A-light
- **Why:** read-model
- **Depends on:** G2, G3, G4, G5, G6

- [x] KPIs: today’s appointments, active patients, unpaid/issued invoices,
      low-stock items
- [x] Weekly visits Mon–Sun; recent `activity_logs`; today’s upcoming table
- [x] Feature test: factory/seeded counts match cards

---

### G8 — Reports (Wave 1 subset)

- **Mode:** A-light
- **Why:** read-only aggregations
- **Depends on:** G7

- [x] Hub: Daily appointments, Patient registration, Outstanding balances,
      Payments (incl. method breakdown), Inventory stock, Low stock, Treatment
      statistics
- [x] Date-range filters; revenue/payment totals match completed payments in
      range
- [x] Accountant + Admin see revenue; Dentist treatment stats scoped to self;
      Lab no finance
- [x] Feature tests per report + 403
- [x] Browser test: Admin opens the reports hub, applies a date range, and sees
      matching payment/ops totals on the page and in the database

Wave 2 reports (cash close, lab, insurance, expiry, audit) land with G10–G15.

---

### G9 — Demo seed and screenshot parity

- **Mode:** A-light
- **Why:** seeder
- **Depends on:** G8

- [x] `db:seed` loads screenshot named records + DCMS Ahmed Ali example +
      generate rules
- [x] Six staff + three dentists log in with `password12`
- [x] Maria Santos, calendar appointments, three SKUs, FEE catalog present
- [x] Dashboard KPIs match `kpis` in golden-smile example JSON
- [x] Pest uses factories, not the 1,284-patient generate set
- [x] Browser test: demo staff can log in with `password12` and the dashboard
      shows the seeded Golden Smile names/KPIs

---

### G10 — Dental chart, encounters, sign-off

- **Mode:** A
- **Why:** clinical lock + odontogram
- **Depends on:** G9

- [x] Encounter per completed visit (`ENC-…`); SOAP notes; draft then sign;
      signed notes cannot be silently edited (amendment row required)
- [x] FDI odontogram: statuses + surfaces from `dcms.json` dental_chart; tooth
      history on patient
- [x] Treatment plan + items; acceptance status
- [x] Feature tests: sign lock, amendment, 403 receptionist write
- [x] Browser test: Dentist signs an encounter and cannot silently edit it;
      receptionist cannot write

---

### G11 — Laboratory orders

- **Mode:** A-light
- **Why:** lab workflow; money stays on invoices
- **Depends on:** G10

- [x] Lab staff/Dentist/Admin CRUD lab orders (`LAB-…`) with DCMS lab_statuses
- [x] Lab role can access lab module; Receptionist 403
- [x] Feature tests: status transitions + 403
- [x] Browser test: Lab staff move an order through DCMS statuses; Receptionist
      cannot open the module

---

### G12 — Inventory advanced (batch, expiry, PO)

- **Mode:** A
- **Why:** inventory integrity
- **Depends on:** G6, G9

- [x] Batches + expiry; cannot consume expired; suppliers + purchase orders
      (`PO-…`)
- [x] Stock adjustment requires Admin (or authorized) confirmation
- [x] Low-stock and expiry alerts
- [x] Feature tests: expired block, PO receive increases qty, unauthorized
      adjust 403
- [x] Browser test: receive a PO, see quantity increase, and expired stock
      cannot be consumed

---

### G13 — Finance extras

- **Mode:** A
- **Why:** money
- **Depends on:** G5, G9

- [x] Expenses (Accountant/Admin); daily cash closing; payment
      plans/installments; insurance claim stub (provider + status)
- [x] Mobile-money daily reconciliation record (system totals vs entered
      provider total)
- [x] Feature tests: closing, expense 403 dentist, plan allocations ≤ balance
- [x] Browser test: Accountant records an expense and a cash close; dentist is
      blocked

---

### G14 — Notification templates (no live SMS)

- **Mode:** A-light
- **Why:** templates only
- **Depends on:** G3, G5

- [x] Seed DCMS communication_templates; Admin can edit
- [x] Queue an in-app/audit “would send” reminder for appointments at 48/24/2
      hours (scheduler) **without** an SMS provider
- [x] Feature tests: template CRUD admin-only; no provider credentials stored on
      payments
- [x] Browser test: Admin edits a template; scheduler writes a would-send audit
      without calling an SMS provider

---

### G15 — Imaging orders (metadata)

- **Mode:** A-light
- **Why:** orders/attachments, not a PACS
- **Depends on:** G10

- [x] Imaging order + result metadata; optional file on Laravel disk
- [x] Dentist/Admin write; no new npm DICOM viewer
- [x] Feature tests: create order, 403 receptionist write
- [x] Browser test: Dentist creates an imaging order with optional file;
      receptionist cannot write

---

## 10. Sequencing and modes

```
Wave 1:
G0 (A) → G1 (A) → G2 (A) → G3 (A) → G4 (A) → G5 (A) → G7 (A-light) → G8 (A-light) → G9 (A-light)
                 ↘ G6 (A-light) ↗

Wave 2:
G9 → G10 (A) → G11 (A-light)
          ↘ G15 (A-light)
G9 → G12 (A)   [also depends G6]
G9 → G13 (A)   [also depends G5]
G3+G5 → G14 (A-light)
```

Do not start Wave 2 until G9 is completed (or user explicitly skips G9).

**Mode rule:** auth/session, record ownership, money, signed clinical lock → A.
Shell/seed/reports/templates → A-light.

**Tests:** each G-id adds Pest for shipped behavior. HTTP feature tests cover
auth, validation, 403s, and persistence. Browser tests in `tests/Browser` cover
the staff click/fill path through the Vue page to the visible result **and** the
database (or other backend) result. Run the narrowest set with
`./vendor/bin/sail artisan test --compact tests/Browser` (or the touched Feature
file), then ask for the full suite.

**UI G-ids:** not done until a Pest browser test exercises the happy path
(button/form → expected page/UI → expected DB or other backend state). HTTP-only
coverage is not enough for pages staff use. Install Playwright browsers with
`PLAYWRIGHT_BROWSERS_PATH=0 npx playwright install chromium`.

---

## 11. Out of scope (G0–G15)

- Patient portal, online self-booking
- Live SMS/WhatsApp/email providers, live ZAAD/Sahal/eDahab/MyCash APIs
- REST `/api/v1` (JSON contract is a future map)
- Multi-branch, starter-kit teams
- Somali/Arabic i18n
- Periodontal full charting, DICOM viewer, sterilization/infection-control ops
  module
- New Composer/npm packages without approval
- Mobile apps
- Git commit/PR unless asked in the goal

---

## 12. Open questions / mappings

| Topic                         | Resolution                                             |
| ----------------------------- | ------------------------------------------------------ |
| JSON vs Golden Smile brand    | Brand Golden Smile; domain DCMS                        |
| ₱ screenshots vs USD JSON     | **USD**                                                |
| Manila vs Mogadishu           | **Africa/Mogadishu**                                   |
| 3 login roles vs 6 JSON roles | **6 roles**; login lists all six                       |
| Room-only vs chairs           | **Chairs** inside rooms; calendar still labels rooms   |
| JSON decimal money            | **Integer cents** (D11)                                |
| `/api/v1`                     | Deferred (D13)                                         |
| Figure 7 billing screenshot   | Still missing; G5 from JSON billing + thesis text      |
| JSON `users: []`              | Demo users live in golden-smile example JSON           |
| 2FA                           | JSON disabled; do not build in G0–G15                  |
| Encrypt at rest               | APP_KEY + HTTPS; column-level encryption not in G0–G15 |
