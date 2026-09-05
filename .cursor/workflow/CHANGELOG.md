# Workflow CHANGELOG

Per-run fix log for goal-workflow Corrector appends. Architecture goal completions go in root `changelog.md`.

## 2026-09-05 — Patient permanent delete billing guard — correct pass

### Fixed

- **R13** — Shared `canPermanentlyDelete` / `patientHasBillingRecords` for show `canDelete` and destroy eligibility; dialog copy is all-or-nothing with cannot-be-undone wording (`PatientController.php`, `Show.vue`, `PatientTest.php`)
- **R14** — Destroy locks archived patient row inside transaction, re-checks billing, then audit + `forceDelete` (`PatientController.php`, `PatientTest.php`)

### Notes

- Findings touched: R13, R14 fixed
- Tests / checks run: `./vendor/bin/sail exec laravel.test vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan test --compact tests/Feature/PatientTest.php` — 38 passed

## 2026-09-05 — Frontend form field vs backend data audit — correct pass

### Fixed

- **R1** — Preserve existing `is_critical` when absent on medical sync; Edit posts hidden critical flags (`PatientController.php`, `Edit.vue`, `PatientTest.php`)
- **R2** — Treatment create appointment options use `linkableAppointmentStatuses()` (`TreatmentController.php`, `TreatmentTest.php`)
- **R3** — Outstanding balances filter by `issued_at` within range; removed date filter from inventory stock/low-stock snapshots (`ReportsQuery.php`, `InventoryStock.vue`, `LowStock.vue`, `ReportsTest.php`)
- **R4** — Chart plan-item form adds fee catalog select and copies description/fee (`PatientChart.vue`)
- **R5** — Patient search, bookable exists, and create prefills limited to `PatientStatus::Active` (`PatientController.php`, `AppointmentValidationRules.php`, lab/imaging/treatment controllers, `PatientTest.php`)
- **R6** — Edit dialog prefills duration; blank posted duration leaves `ends_at` unchanged (`appointments/Index.vue`, `AppointmentController.php`, `AppointmentTest.php`)
- **R7** — Consume batch options omit expired batches (`InventoryController.php`, `InventoryTest.php` browser)
- **R8** — PO line expiry input marked `required` (`purchase-orders/Create.vue`)
- **R9** — Appointment store/update use `activeFeeItemExistsRule()` (`StoreAppointmentRequest.php`, `UpdateAppointmentRequest.php`, `AppointmentTest.php`)
- **R10** — Lab/imaging store rules scope encounter/treatment to patient and require active dentist/patient (`StoreLabOrderRequest.php`, `StoreImagingOrderRequest.php`)
- **R11** — Fortify reset-password view passes `passwordRules` (`FortifyServiceProvider.php`)
- **R12** — Invoice detail exposes `remaining_refundable_cents`; refund picker lists only payments with remainder (`BillingController.php`, `billing/Show.vue`, `BillingTest.php`)
- **T5** — Archived patient permanent delete: policy, destroy route/action, show dialog, audit + forceDelete with invoice/payment guard (`PatientPolicy.php`, `PatientController.php`, `Show.vue`, `PatientTest.php`, `FormAbuseTest.php`, browser `PatientTest.php`)

### Notes

- Findings touched: R1–R12 fixed; T5 implemented
- Tests / checks run: `./vendor/bin/sail exec laravel.test vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan wayfinder:generate`; `./vendor/bin/sail npm run build`; `./vendor/bin/sail artisan test --compact tests/Feature/PatientTest.php tests/Feature/AppointmentTest.php tests/Feature/TreatmentTest.php tests/Feature/ReportsTest.php tests/Feature/BillingTest.php tests/Feature/FormAbuseTest.php tests/Feature/InventoryTest.php` — 242 passed; `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Browser/PatientTest.php tests/Browser/InventoryTest.php` — 7 passed

## 2026-09-05 — Full page, form, and model coverage — implementer defects

### Fixed

- **Report dates** — Invalid `from`/`to` query strings no longer throw `InvalidFormatException`; fall back to month-start / today (`app/Services/ReportDateRange.php`)
- **Admin patient chart** — `TreatmentPlanController.updateItem.form(plan.id, item.id)` crashed the page (Wayfinder two-param helpers take `[id, id]` or a named object). Chart with plan items now renders Save tooth / Update status (`resources/js/pages/chart/PatientChart.vue`)

### Notes

- Tasks touched: T3, T4, T5
- Tests / checks run: Feature coverage 259 passed; Browser smoke + form abuse 49 passed; Pint dirty

## 2026-09-04 — G13 Finance extras — correct pass

### Fixed

- **R1** — Cash/MM system totals net Completed inflows minus same-day Refunded outflows (method/provider/date); refund feature tests added (`DailyCashClosingService.php`, `MobileMoneyReconciliationService.php`, `FinanceExtrasTest.php`)
- **R2** — Cumulative active plan allocations capped at invoice balance via `PaymentPlan::activeAllocatedCentsForInvoice()`; two-plan overflow test added (`StorePaymentPlanRequest.php`, `PaymentPlan.php`, `FinanceExtrasTest.php`)
- **R3** — Payment plan create locks invoice with `lockForUpdate()` and re-validates allocatable balance inside transaction (`PaymentPlanController.php`)
- **R4** — Daily close and MM recon wrap system-total query + insert in `DB::transaction` (`ExpenseController.php`)
- **R5** — Removed server-computed money fields from `DailyCashClosing` / `MobileMoneyReconciliation` fillable (`DailyCashClosing.php`, `MobileMoneyReconciliation.php`)
- **R6** — G13 dollar fields reject excess precision with `decimal:0,2` on FormRequests; truncation test added (`StoreExpenseRequest.php`, `StoreDailyCashClosingRequest.php`, `StoreMobileMoneyReconciliationRequest.php`, `StorePaymentPlanRequest.php`, `FinanceExtrasTest.php`)
- **R7** — Dentist and receptionist 403 tests for MM recon, payment-plans, insurance-claims POSTs (`FinanceExtrasTest.php`)
- **R8** — Duplicate daily close returns validation error; unique violation mapped to `closing_date` error; test added (`ExpenseController.php`, `FinanceExtrasTest.php`)

### Deferred / wontfix

- **R9–R11** — Low/Question findings left open for orchestrator backlog / product confirmation.

### Notes

- Findings touched: R1–R8 fixed; R9–R11 unchanged (open)
- Tests / checks run: `./vendor/bin/sail exec laravel.test vendor/bin/pint --dirty --format agent`; `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Feature/FinanceExtrasTest.php tests/Browser/ExpensesBrowserTest.php tests/Feature/BillingTest.php` — 48 passed

## 2026-09-04 — G12 Inventory advanced (batch, expiry, PO) — correct pass

### Fixed

- **R1** — PO receive uses `lockForUpdate()` inside transaction and re-checks `isReceivable()` before mutating stock; repeat receive 403 test added (`InventoryStockService.php`, `InventoryTest.php`)
- **R2** — `isExpired()` compares clinic calendar dates so expiry-day stock remains consumable through the day; feature test added (`InventoryBatch.php`, `InventoryTest.php`)
- **R3** — AdjustmentIn/Out and initial stock route through batch-aware `adjustStockIn`/`adjustStockOut` (FEFO, expiry-aware); item qty tracks batch sum (`InventoryStockService.php`, `InventoryController.php`)
- **R4** — `consumeFromBatch` validates and locks batch/item rows inside transaction before decrement (`InventoryStockService.php`)
- **R5** — Feature tests for repeat PO receive 403, null-expiry receive rejection, and item–batch qty invariant after receive + consumption (`InventoryTest.php`)
- **R6** — Initial stock and AdjustmentIn require expiry and create `InventoryBatch`; Vue create/adjust forms expose expiry fields (`StoreInventoryItemRequest.php`, `AdjustInventoryItemRequest.php`, `Index.vue`)
- **R7** — PO receive rejects lines without expiry; `StorePurchaseOrderRequest` requires expiry on create (`InventoryStockService.php`, `StorePurchaseOrderRequest.php`)

### Deferred / wontfix

- **R8–R10** — Low findings left open for orchestrator backlog / atomic-close docs sync.

### Notes

- Findings touched: R1–R7 fixed; R8–R10 unchanged (open)
- Tests / checks run: `./vendor/bin/sail exec laravel.test vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail npm run build`; `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Feature/InventoryTest.php tests/Browser/InventoryTest.php` — 24 passed

## 2026-09-04 — G10 Dental chart, encounters, sign-off — correct pass

### Fixed

- **R1** — Browser test: receptionist direct chart/encounter URL visits hide write UI; PATCH/POST write attempts return 403 with unchanged DB (`tests/Browser/EncounterTest.php`)
- **R2** — Treatment plan item acceptance select on create + PATCH `updateItem` with policy; Vue status update form per item (`TreatmentPlanController.php`, `UpdateTreatmentPlanItemRequest.php`, `PatientChart.vue`, `routes/web.php`)
- **R3** — Removed `signed_at`/`signed_by` from `SoapNote` fillable; added `SoapNote::sign(User)`; `EncounterController::sign` uses it; regression test PATCH body cannot forge signature (`SoapNote.php`, `EncounterController.php`, `EncounterTest.php`)
- **R4** — Feature tests: receptionist GET chart/encounter 403, nurse chart read-only, plan+item+acceptance happy path (`EncounterTest.php`)
- **R5** — Optional validated `encounter_id` on odontogram update; auto-link latest unsigned encounter; tooth history linkage tests (`UpdateOdontogramRequest.php`, `PatientChartController.php`, `EncounterTest.php`)

### Deferred / wontfix

- **R6–R8** — Low findings left open for orchestrator backlog.
- **R9** — Question (encounter-dentist ownership) left open; no policy change.

### Notes

- Findings touched: R1–R5 fixed; R6–R9 unchanged (open)
- Tests / checks run: `./vendor/bin/sail exec laravel.test vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail npm run build`; `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Feature/EncounterTest.php tests/Browser/EncounterTest.php tests/Browser/NavigationTest.php` — 22 passed

## 2026-09-03 — G5 Billing and payments — correct pass

### Fixed

- **R1** — Refund amount validations moved inside `DB::transaction` after `lockForUpdate()`; re-reads `amount_paid_cents` under lock (`PaymentProcessor.php`, `BillingTest.php`)
- **R2** — Cumulative refund cap per original payment via `SUM` of refunded rows sharing `reference_number` (`PaymentProcessor.php`, `BillingTest.php`)
- **R3** — Integer-safe dollar→cents via `ConvertsDollarAmounts` trait (string split, no float multiply) (`ConvertsDollarAmounts.php`, `StorePaymentRequest.php`, `StoreRefundRequest.php`)
- **R4** — Added `Gate::authorize('pay'|'refund', $invoice)` in `BillingController` pay/refund actions (`BillingController.php`)
- **R5** — Added Pest: dentist pay 403, refund > original 422, double-refund same payment 422, sequential over-refund guard (`BillingTest.php`)

### Deferred / wontfix

- **R6** — Invoice + line items transaction wrap deferred (Low, out of scope).

### Notes

- Findings touched: R1–R5 fixed; R6 left open (Low, backlog)
- Tests / checks run: `vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan test --compact --filter=BillingTest` — 24 passed
- R1 concurrent behavior verified via sequential over-refund test (lock re-validates `amount_paid_cents` after first refund)

## 2026-09-03 — G4 Treatments and prescriptions — correct pass

### Fixed

- **R1** — `appointment_id` rejects terminal statuses (`cancelled`, `no_show`, `completed`); `completeLinkedAppointment` skips terminal rows (`AppointmentValidationRules.php`, `StoreTreatmentRequest.php`, `TreatmentController.php`, `TreatmentTest.php`)
- **R2** — Store validation reuses `activeDentistExistsRule()` and new `activeFeeItemExistsRule()` (`AppointmentValidationRules.php`, `StoreTreatmentRequest.php`, `FeeItemFactory.php`, `TreatmentTest.php`)
- **R3** — Store validation uses `bookablePatientExistsRule()` for non-archived patients (`StoreTreatmentRequest.php`, `TreatmentTest.php`)
- **R4** — Added Pest coverage: store-as-completed links appointment, duplicate `appointment_id` 422, receptionist/nurse GET create 403 (`TreatmentTest.php`)

### Deferred / wontfix

- **R5** — Patient picker 200-row cap deferred (Low, out of scope).

### Notes

- Findings touched: R1–R4 fixed; R5 left open (Low, out of scope)
- Tests / checks run: `vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan test --compact --filter=TreatmentTest` — 21 passed

## 2026-09-03 — G3 Appointment scheduling — correct pass

### Fixed

- **R1** — Overlap query excludes `NoShow` alongside `Cancelled`; `Rescheduled` rows at current times still block (`AppointmentScheduler.php`, `AppointmentTest.php`)
- **R2** — Book/update validation requires non-archived, non-trashed patients via shared `AppointmentValidationRules` (`StoreAppointmentRequest.php`, `UpdateAppointmentRequest.php`, `AppointmentTest.php`)
- **R3** — Dentist/chair validation requires `is_active = true` (`AppointmentValidationRules.php`, `AppointmentTest.php`)
- **R4** — Check-in restricted to `scheduled`/`confirmed`; invalid transitions return 422 (`AppointmentController.php`, `AppointmentTest.php`)
- **R5** — MySQL `GET_LOCK`/`RELEASE_LOCK` in `AppointmentNumberGenerator`; store retries on `UniqueConstraintViolationException` (`AppointmentNumberGenerator.php`, `AppointmentController.php`)
- **R6** — Added Admin book, no-show slot reuse, archived patient 422, inactive dentist/chair 422, and check-in transition tests (`AppointmentTest.php`, factories)

### Deferred / wontfix

- **R10** — `completed` overlap left unchanged per orchestrator; product intent deferred.

### Notes

- Findings touched: R1–R6 fixed; R7–R9 left open (Low, out of scope); R10 left open (Question, deferred)
- Tests / checks run: `./vendor/bin/sail exec laravel.test vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan test --compact --filter=AppointmentTest` — 22 passed

## 2026-09-02 — G2 Patient management — correct pass

### Fixed

- **R1** — Shared `PatientValidationRules` strips empty nested medical rows in `prepareForValidation`; browser-shaped blank fields no longer fail `required` (`app/Concerns/PatientValidationRules.php`, `StorePatientRequest.php`, `PatientTest.php`)
- **R2** — Id-keyed medical sync with fresh scoped queries (fixes relation `whereKey` accumulation); `Edit.vue` v-for round-trips all rows plus add slot (`PatientController.php`, `Edit.vue`, `PatientTest.php`)
- **R3** — Duplicate first+last+DOB check on update excluding current patient; reuses shared validator with `confirm_duplicate` (`UpdatePatientRequest.php`, `PatientValidationRules.php`, `PatientTest.php`)
- **R4** — `PatientNumberGenerator` uses `lockForUpdate()` inside store transaction for sequential `PAT-{YYYY}-{#####}` (`PatientNumberGenerator.php`)
- **R5** — Expanded authz tests: Accountant/Lab forbidden on show/create/update; Dentist show 200 + update 403; Nurse update 403 (`PatientTest.php`)
- **R6** — Index uses `withTrashed()`; archived badge on list; search finds archived by patient number (`PatientController.php`, `Index.vue`, `PatientTest.php`)

### Deferred / wontfix

- **R9** — Index audit logging deferred: GOAL Done-when is show-only; architecture “index counts as access” unresolved.

### Notes

- Findings touched: R1–R6 fixed; R7/R8 left open (Low, out of scope); R9 deferred
- Tests / checks run: `vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan test --compact --filter=PatientTest` — 22 passed; `./vendor/bin/sail artisan test --compact` — 105 passed

## 2026-09-02 — G1 Domain schema — correct pass

### Fixed

- **R1** — Replaced `cascadeOnDelete()` with `restrictOnDelete()` on financial parent FKs (`invoices.patient_id`, `invoice_items.invoice_id`, `payments.invoice_id`/`patient_id`, `mobile_money_transactions.payment_id`, `receipts.payment_id`) (`database/migrations/2026_09_02_221508_*` through `221518_*`)
- **R2** — Changed `issued_by`, `received_by`, and `prescriber_id` staff FKs to `restrictOnDelete()` so user deletion cannot cascade into financial/clinical rows (`database/migrations/2026_09_02_221508_*`, `221513_*`, `221504_*`)
- **R4** — `PatientFactory::archived()` now soft-deletes after create; test asserts default queries exclude archived patients (`database/factories/PatientFactory.php`, `tests/Feature/DomainSchemaTest.php`)
- **R5** — `PaymentFactory` derives `patient_id` from invoice via `afterMaking`; removed independent `Patient::factory()` default (`database/factories/PaymentFactory.php`, `tests/Feature/DomainSchemaTest.php`)
- **R6** — `zaad()` factory populates `verified_by` and `verified_at`; DomainSchemaTest asserts verifier metadata (`database/factories/PaymentFactory.php`, `tests/Feature/DomainSchemaTest.php`)
- **R7** — Added `AppointmentStatus::InTreatment = 'in_treatment'` (`app/Enums/AppointmentStatus.php`)
- **R8** — Added `InvoiceStatus::WrittenOff = 'written_off'`; `PaymentStatus` unchanged (DCMS `payment_statuses` omits `written_off`) (`app/Enums/InvoiceStatus.php`)

### Deferred / wontfix

- **R3** — Architecture §2 allows SQLite for local/tests; T1 still uses Sail MySQL for `migrate:fresh`. No `phpunit.xml` change.

### Notes

- Tasks touched: review findings R1–R8 (G1 schema/factories)
- Tests / checks run: `vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan migrate:fresh --no-interaction`; `./vendor/bin/sail artisan test --compact` — 83 passed

## 2026-09-02 — G0 Clinic foundation — T5 test gate

### Fixed

- **T5** — Staff GET 403s returned 500 because the empty base `Controller` has no `AuthorizesRequests`; `StaffController` now uses `Gate::authorize('viewStaff'|'createStaff')` (`app/Http/Controllers/Settings/StaffController.php`)

### Notes

- Tasks touched: T5
- Tests / checks run: `vendor/bin/pint --dirty --format agent`; `./vendor/bin/sail artisan test --compact` — 79 passed

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
