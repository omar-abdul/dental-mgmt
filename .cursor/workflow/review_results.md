# Review results — Frontend form field vs backend data audit

- Date: 2026-09-05
- Mode: verify
- Scope: R1–R14 corrector pass + T5 `patients.destroy`
- Goal: Form-data fixes + archived patient delete (`.cursor/workflow/GOAL.md`, Mode A)

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | High | fixed | `resources/js/pages/patients/Edit.vue` | Medical `is_critical` wiped on every patient save |
| R2 | Medium | fixed | `app/Http/Controllers/TreatmentController.php` | Linked-appointment dropdown includes Completed visits |
| R3 | Medium | fixed | `app/Services/ReportsQuery.php` | Date-range filters ignored on outstanding/stock/low-stock |
| R4 | Medium | fixed | `resources/js/pages/chart/PatientChart.vue` | Plan-item form ignores loaded fee catalog |
| R5 | Medium | fixed | `app/Http/Controllers/PatientController.php` | PatientPicker includes inactive patients |
| R6 | Medium | fixed | `resources/js/pages/appointments/Index.vue` | Edit duration not prefilled; empty submit rewrites length |
| R7 | Low | fixed | `app/Http/Controllers/InventoryController.php` | Expired batches still selectable on consume (B23) |
| R8 | Low | fixed | `resources/js/pages/inventory/purchase-orders/Create.vue` | PO line expiry required by server, not by the control |
| R9 | Medium | fixed | `app/Http/Requests/StoreAppointmentRequest.php` | Appointment `fee_item_id` allows inactive catalog rows |
| R10 | Low | fixed | `app/Http/Requests/StoreLabOrderRequest.php` | Lab/imaging encounter/treatment exists rules not patient-scoped |
| R11 | Low | fixed | `app/Providers/FortifyServiceProvider.php` | Reset-password view omits `passwordRules` |
| R12 | Low | fixed | `resources/js/pages/billing/Show.vue` | Refund picker still lists fully refunded originals |
| R13 | Medium | fixed | `app/Http/Controllers/PatientController.php` | `canDelete` ignores invoice/payment billing guard |
| R14 | Medium | fixed | `app/Http/Controllers/PatientController.php` | Permanent delete lacks row lock + in-transaction billing re-check |

## Assessment overview

- Guidelines: G8 advertises date-range filters; three reports (and the hub outstanding card) parse `from`/`to` into props but never apply them. D5 fee catalog is loaded for chart plan items and never bound. D7 archived patients are excluded from pickers; inactive patients are not. D11 cents/dollars and D12 payment methods are consistent on billing/expenses.
- Blast radius: Patient edit save is a daily receptionist path — R1 silently clears critical allergy/condition/med flags. Appointment edit save without touching duration can change `ends_at` (R6). Treatment create shows unlinkable Completed appointments (R2).
- Security: No Critical auth bypass in option lists. Dentist/chair/fee dropdowns are active-only on the pages that matter. R10 is POST-forge of another patient’s encounter/treatment id while the dropdown itself is correctly patient-scoped. Lab/imaging dentist dropdowns are active-only; store rules are looser (`exists` without `is_active`).
- Readability: Option helpers (`dentistOptions`, `feeItemOptions`) are consistent across appointments/treatments/lab/imaging. Chart `feeItems` prop with no consumer is dead weight.
- Extensibility: Expense categories are four hardcoded strings with `string|max:100` validation (no enum). Imaging create offers every status, not a create-time subset.
- Cohesiveness: Treatment procedures already require active fee items; appointment store/update do not (R9). Patient show renders `is_critical`; edit discards it (R1).

**Correct backend-sourced wiring (no finding):** Login/forgot/confirm (no option lists); profile name/email from `auth.user`; staff `ClinicRole::cases()` (six roles); notification template bodies from DB; patient create/edit gender enum; appointment dentists (active `dentists` table) and chairs (active chairs); appointment/treatment fee dropdowns (active `fee_items`); treatment dentist + default dentist from the user’s dentist profile; billing payment methods / MM providers / verification statuses (D12); payment amount prefill from `balance_cents`; expenses cash-close and MM recon system totals computed server-side; reports daily appointments, patient registration, payments, and treatment statistics honor the date range (Dentist self-scoped on clinical reports); odontogram FDI/status/surface vs `dental_chart`; SOAP prefill; lab Show status buttons = `allowedTransitions()`; inventory category and movement-type enums; PO supplier/item lists (no inactive column exists); imaging type enum.

Catalog: `.cursor/workflow/form-field-catalog.md`.

## Critical

None.

## High

### R1 — Medical `is_critical` wiped on every patient save
- Severity: High
- Status: fixed
- Path: `resources/js/pages/patients/Edit.vue`
- Area: blast-radius
- Finding: Edit loads `is_critical` on allergy/condition/medication props (and Show displays “(critical)”) but the form only posts `id` and `label`. Sync treats a missing flag as false, so any save that includes those arrays clears critical flags.
- Evidence: `Edit.vue` types include `is_critical` (line 19) but rows submit hidden id + label only (lines 180–187, 204–213, 230–238). `PatientController::syncMedicalCollection` sets `'is_critical' => (bool) ($item['is_critical'] ?? false)` (~348). Create has no critical UI either (old B9); edit is data loss, not just a missing toggle.
- Suggested fix: Preserve existing `is_critical` when the key is absent (or post hidden `is_critical` from the loaded prop). Do not default missing to false on update.

## Medium

### R2 — Linked-appointment dropdown includes Completed visits
- Severity: Medium
- Status: fixed
- Path: `app/Http/Controllers/TreatmentController.php`
- Area: cohesiveness
- Finding: Treatment create lists untreated appointments that are not Cancelled/NoShow, including **Completed**. Store validation `linkableAppointmentExistsRule` excludes Completed, so staff pick a visible option and get 422.
- Evidence: `appointmentOptions()` `whereNotIn` Cancelled, NoShow (~365-368). `AppointmentValidationRules::linkableAppointmentStatuses()` is Scheduled/Confirmed/CheckedIn/InProgress/InTreatment/Rescheduled only (~40-47).
- Suggested fix: Filter `appointmentOptions` with the same status list as `linkableAppointmentStatuses()`.

### R3 — Date-range filters ignored on outstanding/stock/low-stock
- Severity: Medium
- Status: fixed
- Path: `app/Services/ReportsQuery.php`
- Area: guidelines
- Finding: G8 requires date-range filters. Outstanding balances, inventory stock, and low stock still render `ReportDateRangeFilter` and pass `filters.from`/`to`, but the queries ignore the range. Hub outstanding summary is the same snapshot.
- Evidence: `ReportsController::outstandingBalances` parses `$range` then calls `$reports->outstandingBalances()` with no range (~53-57). `ReportsQuery::outstandingBalances()` filters status + `balance_cents > 0` only (~128-136). `inventoryStock()` / `lowStock()` likewise (~239-283). Payments and daily appointments do use the range.
- Suggested fix: Either apply `issued_at` / as-of date to those queries, or remove the date filter from those three pages and the hub outstanding card.

### R4 — Plan-item form ignores loaded fee catalog
- Severity: Medium
- Status: fixed
- Path: `resources/js/pages/chart/PatientChart.vue`
- Area: cohesiveness
- Finding: Chart passes active `feeItems` (id, label, price_cents). The add-item form has free-text description, tooth FDI, and **fee in cents** — no `fee_item_id` select. D5 says the fee catalog is the procedure/price source. Store already accepts `fee_item_id` and can copy price when `fee_cents === 0`.
- Evidence: `PatientChartController.php` ~97-106; `PatientChart.vue` plan-item form ~322-365; `StoreTreatmentPlanItemRequest.php` ~30; `TreatmentPlanController.php` ~24-31.
- Suggested fix: Add a fee-item select bound to `feeItems`; set description/fee_cents from the chosen catalog row (staff can still override description).

### R5 — PatientPicker includes inactive patients
- Severity: Medium
- Status: fixed
- Path: `app/Http/Controllers/PatientController.php`
- Area: cohesiveness
- Finding: Search used by appointment book/edit, treatment create, lab create, and imaging create excludes **Archived** only. `PatientStatus::Inactive` still appears. Dashboard active-patient KPIs already ignore inactive. D7 marks archived read-only; inactive is a first-class status that should not book or start treatments if it means “not currently a patient.”
- Evidence: `PatientController::search` `where('status', '!=', PatientStatus::Archived)` (~37-38). `bookablePatientExistsRule()` same archived-only filter (`AppointmentValidationRules.php` ~14-16). Treatment/lab/imaging `?patient_id=` prefill uses `Patient::find` with no status filter.
- Suggested fix: Restrict search + bookable exists + create prefills to `PatientStatus::Active` (keep archived-only if product intends inactive to still book — confirm first).

### R6 — Edit duration not prefilled; empty submit rewrites length
- Severity: Medium
- Status: fixed
- Path: `resources/js/pages/appointments/Index.vue`
- Area: blast-radius
- Finding: Edit dialog prefills start time and procedure but leaves duration empty. The empty number input is still posted, so `has('duration_minutes')` is true, integer 0 becomes null, and `ends_at` is recalculated from fee default or 30 minutes — a silent reschedule.
- Evidence: `appointments/Index.vue` ~657-664 (no `:default-value`). `AppointmentController::update` ~143-148 uses `$request->has('duration_minutes')` then `integer ?: null`, then recalculates ends when duration/fee/start changed.
- Suggested fix: Prefill duration from `diffInMinutes(starts_at, ends_at)`. Treat empty posted duration as “unchanged” (ignore `has`) unless the user typed a value.

### R9 — Appointment `fee_item_id` allows inactive catalog rows
- Severity: Medium
- Status: fixed
- Path: `app/Http/Requests/StoreAppointmentRequest.php`
- Area: cohesiveness
- Finding: Book/edit dropdowns list active fee items only. Store/update validate `exists:fee_items,id` without `is_active`. Treatment procedures already use `activeFeeItemExistsRule()`.
- Evidence: `StoreAppointmentRequest.php` ~28; `UpdateAppointmentRequest.php` ~31; `AppointmentController::feeItemOptions` filters `is_active` (~385-396); `TreatmentController` + `StoreTreatmentRequest` require active.
- Suggested fix: Use `activeFeeItemExistsRule()` on appointment store/update (nullable still OK).

## Low

### R7 — Expired batches still selectable on consume (B23)
- Severity: Low
- Status: fixed
- Path: `app/Http/Controllers/InventoryController.php`
- Area: cohesiveness
- Finding: Consume batch select loads all batches with `quantity > 0`, including expired. Vue labels “(expired)” but still allows choose; server 422. Already backlog B23.
- Evidence: `InventoryController.php` ~70, ~193-200; `inventory/Index.vue` consume select; `InventoryStockService` rejects expired consume.
- Suggested fix: Filter `whereDate('expiry_date', '>=', today())` (or hide expired in Vue). Leave to B23 unless this run’s Corrector is asked to include Low.

### R8 — PO line expiry required by server, not by the control
- Severity: Low
- Status: fixed
- Path: `resources/js/pages/inventory/purchase-orders/Create.vue`
- Area: cohesiveness
- Finding: Each PO line `expiry_date` is `required|date|after_or_equal:today` but the date input has no `required`. Staff can submit and get a validation error instead of a browser block.
- Evidence: `StorePurchaseOrderRequest.php` ~32; `Create.vue` ~185-192.
- Suggested fix: Mark the input `required` (or make the request nullable if expiry can be filled at receive).

### R10 — Lab/imaging encounter/treatment exists rules not patient-scoped
- Severity: Low
- Status: fixed
- Path: `app/Http/Requests/StoreLabOrderRequest.php`
- Area: security
- Finding: Dropdowns are correctly limited to the selected patient’s treatments/encounters. Validation is bare `exists`, so a crafted POST can attach another patient’s treatment/encounter. Out of the “data pulled” core, but real.
- Evidence: `LabOrderController` treatment/encounter options filter `patient_id`; `StoreLabOrderRequest.php` ~29-31; `StoreImagingOrderRequest.php` encounter `exists` only. Dentist rules similarly omit `is_active`.
- Suggested fix: `Rule::exists(...)->where('patient_id', $this->integer('patient_id'))`; dentist `where('is_active', true)`.

### R11 — Reset-password view omits `passwordRules`
- Severity: Low
- Status: fixed
- Path: `app/Providers/FortifyServiceProvider.php`
- Area: cohesiveness
- Finding: `ResetPassword.vue` requires `passwordRules` and binds it to `PasswordInput`. Fortify reset view passes only `email` and `token`. Security settings pass the string correctly.
- Evidence: `FortifyServiceProvider.php` ~55-58 vs `ResetPassword.vue` ~19-23, 61, 74; `SecurityController.php` ~20-21.
- Suggested fix: Pass `Password::defaults()->toPasswordRulesString()` (same as Security) on the reset view.

### R12 — Refund picker still lists fully refunded originals
- Severity: Low
- Status: fixed
- Path: `resources/js/pages/billing/Show.vue`
- Area: cohesiveness
- Finding: Refund select is `invoice.payments` with `status === 'completed'`. Refunds insert a new Refunded row and leave the original Completed, so a fully refunded payment still appears until `PaymentProcessor` rejects over-refund.
- Evidence: `billing/Show.vue` ~112-114, ~414-420; `PaymentProcessor::refund` keeps original Completed.
- Suggested fix: Exclude originals whose remaining refundable cents are 0 (or mark original non-completed when fully refunded).

### R13 — `canDelete` ignores invoice/payment billing guard
- Severity: Medium
- Status: fixed
- Path: `app/Http/Controllers/PatientController.php`, `resources/js/pages/patients/Show.vue`
- Area: cohesiveness
- Finding: Show `canDelete` used policy only (archived + role). Destroy rejected patients with invoices/payments, but the Delete permanently button still appeared; dialog copy implied partial delete (“except invoices and payments”).
- Evidence: `PatientController::show` passed `can('delete', $patient)` only; `destroy` checked billing before delete; `Show.vue` dialog mentioned exceptions.
- Suggested fix: Shared `canPermanentlyDelete` helper (policy + no billing rows) for show and destroy eligibility; hide button when false; all-or-nothing dialog copy.

### R14 — Permanent delete lacks row lock + in-transaction billing re-check
- Severity: Medium
- Status: fixed
- Path: `app/Http/Controllers/PatientController.php`
- Area: blast-radius
- Finding: Billing guard ran outside `DB::transaction` with no row lock, allowing a race where billing could be created between check and `forceDelete`.
- Evidence: Pre-transaction `invoices()->exists()` / `payments()->exists()` then audit + `forceDelete` inside transaction without `lockForUpdate`.
- Suggested fix: Inside transaction, lock patient (`withTrashed` + `lockForUpdate`), re-check billing, throw same `ValidationException` if blocked, then audit + `forceDelete`.

## Verify pass notes

> Re-checked in code; mandatory Sail feature gate 244 passed; browser PatientTest 3 passed after `npm run build`. No open Critical/High.

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R1 | fixed | fixed | `is_critical` preserved on update |
| R2 | fixed | fixed | Linkable appointment statuses in options |
| R3 | fixed | fixed | Outstanding uses `issued_at`; stock/low-stock snapshots |
| R4 | fixed | fixed | Fee catalog on plan item form |
| R5 | fixed | fixed | Active-only picker/search |
| R6 | fixed | fixed | Duration prefill; blank duration unchanged |
| R7 | fixed | fixed | Expired batches omitted from consume |
| R8 | fixed | fixed | PO expiry required |
| R9 | fixed | fixed | Active fee item on appointments |
| R10 | fixed | fixed | Lab/imaging scoped exists |
| R11 | fixed | fixed | Reset passwordRules |
| R12 | fixed | fixed | Refund remaining cents |
| R13 | fixed | fixed | `canDelete` includes billing guard |
| R14 | fixed | fixed | `lockForUpdate` + in-transaction re-check |
