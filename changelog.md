# Changelog

Completed architecture goals (G0–G15) and substantial scaffolding. Per-run Corrector notes live in `.cursor/workflow/CHANGELOG.md`.

## 2026-09-03 — G7 Dashboard

Replaced the Overview placeholder with live KPIs (today’s appointments, active patients, unpaid/issued invoices, low-stock items), Mon–Sun weekly visits, a recent activity feed, and today’s upcoming table. Counts use clinic timezone. Lab sees a limited dashboard; Accountant sees unpaid invoices without clinical lists; Nurse does not see the unpaid-invoice card.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 198 passed. Pest HTTP as browser-path substitute (factory counts match Inertia props; vacated/inactive/paid/out-of-stock excluded; Lab/Accountant/Nurse scoping).
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G8 (reports)

## 2026-09-03 — Searchable patient picker (UX)

Replaced the patient `<select>` on appointment book/edit and treatment create with a typeahead (`PatientPicker`) that searches by name, patient number, phone, and email. Archived patients are omitted. Dentist/chair/fee catalogs stay as dropdowns.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 190 passed. Pest HTTP as browser-path substitute (`patients.search` 200/403/empty/archived).
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G7 (dashboard) — **done 2026-09-03**

## 2026-09-03 — G5 Billing and payments

Replaced the billing placeholder with invoices generated from completed treatments, integer-cent totals, partial/overpay rules, recorded ZAAD/Sahal/eDahab/MyCash (no live APIs), printable receipts, and Admin/Accountant refunds. Dentist views, cannot generate or pay. Lab 403. Invoices are never hard-deleted.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 183 passed (`BillingTest` 24 + G0–G4/G6 suite). Pest HTTP as browser-path substitute. R1 Critical + R2 High + R3–R5 Medium fixed; R6 deferred (B16).
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G7 (dashboard)

## 2026-09-03 — G4 Treatments and prescriptions

Replaced the treatments placeholder so Dentist/Admin can record diagnosis, fee-item procedures, and a prescription (prescriber is the logged-in user). Completing a treatment may set the linked appointment to `completed`. Patient show lists history. Nurse views, cannot POST Rx. Receptionist is view-only. Critical allergy flags appear on the treatment form.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 159 passed (`TreatmentTest` 21 + G0–G3/G6 suite). Pest HTTP as browser-path substitute. R1 High + R2–R4 Medium fixed; R5 deferred (B15).
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G5 (billing and payments)

## 2026-09-03 — G3 Appointment scheduling

Replaced the appointments placeholder with a day calendar driven by clinic `working_hours`. Receptionist/Admin book, edit, cancel, reschedule, and check-in; Nurse check-in only; Dentist view-only. Overlap on dentist or chair is 422; cancelled and no-show slots do not block. Friday and outside-hours bookings are 422. Sequential `APT-{YYYY}-{#####}`. Cancel/reschedule writes `appointment_revisions`.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 138 passed (`AppointmentTest` 22 + G0–G2/G6 suite). Pest HTTP as browser-path substitute. No Critical/High; R1–R6 fixed; R7–R10 deferred (B11–B14).
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G4 (treatments and prescriptions)

## 2026-09-02 — G6 Inventory (basic)

Replaced the inventory placeholder with four summary cards, search, add, and a table with derived stock badges. Adjustments record movements; quantity cannot go negative. Nurse/Admin/Receptionist write; Dentist view; Accountant/Lab 403.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 116 passed (`InventoryTest` 11 + G0–G2 suite). Pest HTTP as browser-path substitute.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G3 (appointment scheduling)

## 2026-09-02 — G2 Patient management

Replaced the patients placeholder with register, search, show, update, and archive. Receptionist/Admin write; Dentist/Nurse view-only; Accountant/Lab 403. Unique `PAT-{YYYY}-{#####}` numbers; show writes an access audit; duplicate first+last+DOB warns.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 105 passed (`PatientTest` + G0/G1 suite). Pest HTTP as browser-path substitute (create with blank medical fields, archive, 403s).
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G3 (G6 may still run in parallel after G1)

## 2026-09-02 — G1 Domain schema and factories (Wave 1)

Wave 1 tables, models, factories, and DCMS fee/working-hours seeds. Money is integer cents. Financial FKs `restrictOnDelete()` so billing rows are not cascade-wiped (D7).

- **Verified:** `./vendor/bin/sail artisan migrate:fresh --no-interaction` exit 0; `./vendor/bin/sail artisan test --compact` — 83 passed (`DomainSchemaTest` factory graph + G0 suite)
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G2 (G6 may run in parallel after G1)

## 2026-09-02 — G0 Clinic foundation

Retired starter-kit teams, put six clinic roles on `users`, disabled public registration, and shipped Golden Smile login plus navy Wave 1 chrome. Admin can create staff; other roles cannot.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 79 passed (Staff 403s via `Gate::authorize`; six-role `login.store`; placeholder module matrix)
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G1

## 2026-09-02 — DCMS JSON ingested (not a G-id)

Replaced thesis-only domain assumptions with the user DCMS contract: USD, `Africa/Mogadishu`, six roles, chairs, fee catalog, mobile-money recording rules. Split delivery into Wave 1 (G0–G9 operable clinic) and Wave 2 (G10–G15 JSON depth). Golden Smile remains the UI brand.

- **Verified:** `database/data/dcms.json` parses; `architecture.md` G0–G15 with Mode + verify bullets; screenshot fixture reshaped to DCMS fields
- **Packages:** docs + JSON only (no product code)
- **Next implement:** G0

## 2026-09-01 — Architecture authored (not a G-id)

Defined Golden Smile Dental Clinic MIS on the Laravel Vue starter kit (superseded in domain details by 2026-09-02 ingest).

- **Verified:** initial G0–G9 + screenshot-derived demo JSON
- **Packages:** docs + example JSON only
