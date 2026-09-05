# Changelog

Completed architecture goals (G0–G15) and substantial scaffolding. Per-run Corrector notes live in `.cursor/workflow/CHANGELOG.md`.

## 2026-09-05 — Form-data wiring fixes and archived patient delete

Corrected frontend form fields that loaded the wrong backend data (critical medical flags wiped on edit, inactive patients in pickers, Completed appointments on treatment create, unused report date filters, chart plan items ignoring the fee catalog, appointment duration rewrite, expired consume batches, and related validation/UI mismatches). Archived patients can be permanently deleted after a confirmation dialog; invoices/payments still cannot be hard-deleted.

- **Verified:** `./vendor/bin/sail artisan test --compact tests/Feature/PatientTest.php tests/Feature/AppointmentTest.php tests/Feature/TreatmentTest.php tests/Feature/ReportsTest.php tests/Feature/BillingTest.php tests/Feature/InventoryTest.php tests/Feature/FormAbuseTest.php` — 244 passed. `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Browser/PatientTest.php` — 3 passed (after `npm run build`). R1–R14 closed. `vendor/bin/pint --dirty --format agent` (Corrector).
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** none (architecture G0–G15 complete). Ask for a full `./vendor/bin/sail artisan test --compact` suite run.

## 2026-09-05 — Staff-created dentists in pickers

Creating a staff user with the Dentist role only wrote `users`. Appointment/treatment/lab/imaging/chart dropdowns (and the day-calendar columns) read `dentists`, so registered dentists never appeared. `staff.store` now creates an active dentist profile (`display_name` = staff name) in the same transaction. Tests cover that path plus the same “created record shows up elsewhere” contract for patient typeahead and supplier/SKU on PO create.

- **Verified:** `./vendor/bin/sail artisan test --compact tests/Feature/StaffTest.php tests/Feature/PickerOptionsTest.php tests/Browser/StaffTest.php` — 35 passed. `vendor/bin/pint --dirty --format agent`.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** none (architecture G0–G15 complete)

## 2026-09-05 — Full page, form, and model automated coverage

Cataloged every domain model (49), routed Vue page, and mutation, then added Pest coverage: persist each model, GET guest/authorized/403 matrices, empty/invalid/abuse payloads (XSS, SQL-like strings, overlong, extra keys), and Playwright smoke of every staff GET page plus login/patient-create abuse in the UI. Two real defects blocked the suite and were fixed: garbage report `from`/`to` dates no longer 500, and admin patient chart no longer blanks when a treatment-plan item form mounts (Wayfinder two-param `.form()` must take an array or object).

- **Verified:** `./vendor/bin/sail artisan test --compact tests/Feature/ModelCatalogTest.php tests/Feature/HttpPageAccessTest.php tests/Feature/AuthAbuseTest.php tests/Feature/FormAbuseTest.php` — 259 passed. `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Browser/StaffPagesSmokeTest.php tests/Browser/FormAbuseSmokeTest.php` — 49 passed. `vendor/bin/pint --dirty --format agent`.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** none (architecture G0–G15 complete). Ask for a full `./vendor/bin/sail artisan test --compact` suite run.

## 2026-09-05 — Sail MySQL pending G10/G12 tables (inventory_batches 1146)

Inventory index 500ed with `SQLSTATE[42S02]` on `inventory_batches`. G12 code was already shipped; Sail MySQL `laravel` had never recorded several G10 leftover tables and all G12 inventory migrations (G11/G13–G15 had already run). Dropped an empty incomplete `odontogram_surfaces` row set, then applied pending migrations (batch 9). No product code change.

- **Verified:** Table exists; expiry query runs; authenticated `GET /inventory` 200; `./vendor/bin/sail artisan test --compact tests/Feature/InventoryTest.php` — 20 passed
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** none (architecture G0–G15 complete)

## 2026-09-05 — G15 Imaging orders (metadata)

Imaging orders (`IMG-…`) with optional result findings/impression and an optional file on the Laravel `local` disk. Admin/Dentist write; Nurse may view; Receptionist is 403. Imaging nav for authorized roles. No DICOM viewer or new npm packages.

- **Verified:** Orchestrator ran `./vendor/bin/sail artisan test --compact tests/Feature/ImagingOrderTest.php tests/Browser/ImagingOrderTest.php tests/Browser/NavigationTest.php` — 20 passed. File persistence is covered in the feature test (`Storage::fake`); Pest’s in-process browser server does not parse multipart bodies (`@TODO files`), so the browser happy path submits metadata without attaching.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** none (architecture G0–G15 complete)

## 2026-09-04 — G14 Notification templates (no live SMS)

Seeded DCMS communication templates (APT-REMINDER, APT-CONFIRM, PAYMENT-RECEIPT). Admin edits them under Settings → Notifications. Hourly `appointments:queue-reminders` writes idempotent `notification.would_send` audit rows at 48/24/2 hours. No SMS HTTP.

- **Verified:** Orchestrator re-ran `./vendor/bin/sail artisan test --compact tests/Feature/NotificationTemplateTest.php tests/Browser/NotificationTemplateTest.php` — 12 passed.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G15 (imaging orders)

## 2026-09-04 — G13 Finance extras

Accountant/Admin expenses, daily cash closing, payment plans with cumulative allocation caps, insurance claim stubs, and mobile-money daily reconciliation (system vs provider totals, refund-aware). Dentist is 403. Expenses nav for authorized roles.

- **Verified:** Orchestrator re-ran `./vendor/bin/sail artisan test --compact tests/Feature/FinanceExtrasTest.php tests/Browser/ExpensesBrowserTest.php tests/Feature/BillingTest.php` — 48 passed after Corrector (R1–R8). Verifier confirmed R1–R8 in code; no open Critical/High.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G14 (notification templates)

## 2026-09-04 — G12 Inventory advanced (batch, expiry, PO)

Inventory items have batches with expiry; expired stock cannot be consumed. Suppliers and purchase orders (`PO-…`) receive into batches and increase quantity. Stock adjustments are Admin-only. Low-stock and expiry alerts on the inventory index.

- **Verified:** Orchestrator re-ran `./vendor/bin/sail artisan test --compact tests/Feature/InventoryTest.php tests/Browser/InventoryTest.php` — 24 passed after Corrector (R1–R7). Verifier confirmed R1–R7 in code; no open Critical/High.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G13 (finance extras)

## 2026-09-04 — G11 Laboratory orders

Lab staff, Dentist, and Admin CRUD lab orders (`LAB-…`) with DCMS statuses (ordered through fitted/returned/cancelled). Lab module in the sidebar. Receptionist is 403. Lab work is not billed on the order.

- **Verified:** Orchestrator re-ran `./vendor/bin/sail artisan test --compact tests/Feature/LabOrderTest.php tests/Browser/LabOrderTest.php tests/Browser/NavigationTest.php` — 22 passed.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G12 (inventory advanced: batch, expiry, PO)

## 2026-09-04 — G10 Dental chart, encounters, sign-off

Completed visits create `ENC-…` encounters with SOAP draft/sign. Signed notes cannot be silently edited; amendments are required. FDI odontogram (DCMS statuses/surfaces) with tooth history, treatment plans with item acceptance. Admin/Dentist write; Nurse view; Receptionist 403. Chart nav for authorized roles.

- **Verified:** Orchestrator re-ran `./vendor/bin/sail artisan test --compact tests/Feature/EncounterTest.php tests/Browser/EncounterTest.php tests/Browser/NavigationTest.php` — 22 passed after Corrector (R1–R5). Verifier confirmed R1–R5 in code; no open Critical/High.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G11 (laboratory orders)

## 2026-09-04 — G9 Demo seed and screenshot parity

`db:seed` loads Golden Smile named staff, patients (Ahmed Ali `PAT-2026-00001`, Maria Santos), appointments, three SKUs, FEE catalog, and generate extras so dashboard KPIs match `golden-smile.example.json`. Pest uses `GoldenSmileNamedSeeder` only (no 1,284-patient insert). Demo login `a.santos@goldensmile.clinic` / `password12`.

- **Verified:** Orchestrator re-ran `./vendor/bin/sail artisan test --compact tests/Feature/GoldenSmileSeederTest.php tests/Feature/DatabaseSeederTest.php tests/Browser/GoldenSmileLoginTest.php` — 11 passed. Implementer full suite 247 passed.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G10 (dental chart, encounters, sign-off)
- **Note:** Weekly visits `fri: 0` matches the JSON when the seed day is not Friday; seeding on Friday puts today’s 18 appointments in the Friday bucket.

## 2026-09-04 — G8 Reports (Wave 1 subset)

Replaced the reports placeholder with a hub and seven read-only reports (daily appointments, patient registration, outstanding balances, payments with method breakdown, inventory stock, low stock, treatment statistics). Date-range filters use clinic timezone. Payment totals are completed payments in integer cents. Admin/Accountant see finance; Dentist stats are self-scoped; Lab has no finance.

- **Verified:** Orchestrator re-ran `./vendor/bin/sail artisan test --compact tests/Feature/ReportsTest.php tests/Browser/ReportsTest.php tests/Browser/NavigationTest.php` — 24 passed. Implementer full suite 237 passed.
- **Packages:** Laravel app only (no new Composer/npm deps)
- **Next implement:** G9 (demo seed)

## 2026-09-04 — Pest Playwright browser tests (prerequisite before G8)

Added `pestphp/pest-plugin-browser` and Playwright Chromium so staff UI can be tested click-to-database. Retroactive `tests/Browser` coverage for login, dashboard, patients, appointments, treatments, billing, inventory, staff, and role-scoped nav. Completed treatments now have a Generate invoice button. `AGENTS.md` and `architecture.md` require front-to-back browser tests for UI G-ids.

- **Verified:** `./vendor/bin/sail artisan test --compact` — 215 passed (15 browser + G0–G7 HTTP suite). Playwright as the real-browser path.
- **Packages:** `pestphp/pest-plugin-browser` (Composer dev), `playwright` (npm dev)
- **Next implement:** G8 (reports)

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
