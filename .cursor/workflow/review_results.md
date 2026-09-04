# Review results — G13 Finance extras

- Date: 2026-09-04
- Mode: verify
- Scope: G13 finance — `ExpenseController.php`, `PaymentPlanController.php`, `InsuranceClaimController.php`, `DailyCashClosingService.php`, `MobileMoneyReconciliationService.php`, G13 models/policies/requests, `resources/js/pages/expenses/Index.vue`, `AppSidebar.vue`, `ClinicRole`, `tests/Feature/FinanceExtrasTest.php`, `tests/Browser/ExpensesBrowserTest.php`, migration `2026_09_04_152043_create_g13_finance_tables.php`
- Goal: G13 — Finance extras (`.cursor/workflow/GOAL.md`, `architecture.md` §G13, §6 Expenses Admin/Accountant only)

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | High | fixed | `app/Services/DailyCashClosingService.php` | Cash/MM system totals ignore same-day refunds (Completed rows remain) |
| R2 | High | fixed | `app/Http/Requests/StorePaymentPlanRequest.php` | Multiple plans per invoice can each allocate up to full balance |
| R3 | High | fixed | `app/Http/Controllers/PaymentPlanController.php` | Payment plan create lacks invoice row lock (concurrent over-allocation) |
| R4 | Medium | fixed | `app/Http/Controllers/ExpenseController.php` | System totals computed outside transaction (stale closing/recon snapshot) |
| R5 | Medium | fixed | `app/Models/DailyCashClosing.php` | Mass-assignable server-authoritative money fields on closing/recon models |
| R6 | Medium | fixed | `app/Concerns/ConvertsDollarAmounts.php` | Extra decimal places truncated silently (no validation reject) |
| R7 | Medium | fixed | `tests/Feature/FinanceExtrasTest.php` | Incomplete 403 matrix for dentist/receptionist on G13 POST endpoints |
| R8 | Medium | fixed | `tests/Feature/FinanceExtrasTest.php` | No test for duplicate daily close or concurrent unique violation handling |
| R9 | Low | deferred | `resources/js/pages/billing/Show.vue` | Payment plans and insurance claims have no billing UI (HTTP-only) |
| R10 | Low | fixed | `architecture.md` | G13 verification bullets still unchecked (docs drift) |
| R11 | Question | deferred | `app/Http/Requests/StoreDailyCashClosingRequest.php` | `closing_date` accepts any date via API (UI posts today only) |

## Assessment overview

- Guidelines: Integer cents and Mogadishu TZ are used consistently; MM recon `system_total_cents` is server-computed (not client-forgeable on current paths). `daily_cash_closings.closing_date` **has** a DB unique index and FormRequest `Rule::unique` — double-close same day is blocked at validation/DB layer, but concurrent requests are untested and may 500. Policies restrict expenses/close/recon/plans/claims to Admin+Accountant per architecture §6. Pint/Wayfinder patterns followed on expenses page.
- Blast radius: Cash close and MM recon read from shared `payments` ledger; refund semantics in `PaymentProcessor` (original stays `Completed`, separate `Refunded` row) interact badly with new reconciliation sums. Payment plans attach to billing invoices with no UI wiring — staff must hit API routes directly for plans/claims.
- Security: Dentist 403 on expenses index/store/cash-close is enforced and tested. No Critical auth bypass found on scoped routes. Defense-in-depth gap: ledger models expose `system_*_cents` / `difference_cents` / `transaction_count` as fillable. Receptionist/Nurse/Lab POST to MM recon, payment-plans, insurance-claims are policy-blocked but not feature-tested.
- Readability: Controllers are thin; services isolate sum logic. Expense index duplicates `systemCashTotalCentsForDate()` call (lines 61–62).
- Extensibility: Payment plan validation mirrors single-shot balance check without cumulative plan tracking — conflicts with G13 “allocations ≤ invoice balance” intent when multiple plans exist. No `paymentPlans()` on `Invoice` model.
- Cohesiveness: `PaymentProcessor` uses `lockForUpdate()` for pay/refund; G13 plan create and cash close omit the same pattern. Refund-aware net totals are not aligned with G5 refund model. Payment plan / insurance claim stubs are backend-only while expenses module is fully surfaced in Vue.

## Critical

None.

## High

### R1 — Cash/MM system totals ignore same-day refunds (Completed rows remain)
- Severity: High
- Status: fixed
- Path: `app/Services/DailyCashClosingService.php`
- Area: cohesiveness
- Finding: Daily cash closing and mobile-money reconciliation sum only `PaymentStatus::Completed` rows. Refunds create a **new** payment with `PaymentStatus::Refunded` while the original payment remains `Completed`. Same-day cash or MM refunds therefore leave the original inflow in `system_*_total_cents` with no offset — net system total is overstated and reconciliation differences are wrong.
- Evidence: `DailyCashClosingService::systemCashTotalCentsForDate()` lines 17–21 filter `status = Completed` and `method = Cash`. `MobileMoneyReconciliationService::systemTotalsForDateAndProvider()` lines 21–36 same filter for MM methods. `PaymentProcessor::refund()` lines 156–168 creates a positive `amount_cents` row with `status = Refunded` without changing the original payment status. `FinanceExtrasTest` cash-close and MM-recon tests use only Completed payments — no refund scenario.
- Suggested fix: Compute net totals: sum Completed inflows minus Refunded outflows (same method/provider/date window), or mark original payments refunded and exclude them. Mirror logic in both services; add feature tests with same-day cash refund and MM refund before close/recon.

### R2 — Multiple plans per invoice can each allocate up to full balance
- Severity: High
- Status: fixed
- Path: `app/Http/Requests/StorePaymentPlanRequest.php`
- Area: security
- Finding: Each payment plan is validated only against the invoice’s current `balance_cents`. There is no check of existing active plans’ installment totals. Two (or more) plans on the same invoice can each allocate up to the full balance, producing cumulative promised installments far above the outstanding balance — violating G13 allocation cap intent.
- Evidence: `withValidator()` line 55 compares `$totalAllocationCents > $invoice->balance_cents` only. `payment_plans` migration has no unique/active constraint on `invoice_id`. `PaymentPlanController` creates plans without querying existing plans. `FinanceExtrasTest` creates a single plan only.
- Suggested fix: Before create, sum `installments.amount_cents` for active plans on the invoice and ensure `$totalAllocationCents + $existingAllocated <= $invoice->balance_cents` (or allow only one active plan per invoice). Add feature test: two sequential plans that individually fit balance but exceed cumulatively → 422.

### R3 — Payment plan create lacks invoice row lock (concurrent over-allocation)
- Severity: High
- Status: fixed
- Path: `app/Http/Controllers/PaymentPlanController.php`
- Area: security
- Finding: Plan creation runs in a transaction but never locks the invoice row. Concurrent POSTs can both read the same `balance_cents`, pass validation, and insert plans whose combined allocations exceed balance — same class of TOCTOU bug `PaymentProcessor::pay()` prevents with `lockForUpdate()`.
- Evidence: `PaymentPlanController::store()` lines 29–48 — `DB::transaction` creates plan/installments without `Invoice::query()->lockForUpdate()`. `StorePaymentPlanRequest::withValidator()` reads `$invoice->balance_cents` outside the transaction. Contrast `PaymentProcessor.php` line 44.
- Suggested fix: Inside the transaction, reload invoice with `lockForUpdate()`, recompute remaining allocatable balance (including existing plans per R2), validate, then insert. Add feature test with simulated concurrent creates or sequential rapid double-submit.

## Medium

### R4 — System totals computed outside transaction (stale closing/recon snapshot)
- Severity: Medium
- Status: fixed
- Path: `app/Http/Controllers/ExpenseController.php`
- Area: extensibility
- Finding: `storeDailyClosing` and `storeMobileMoneyReconciliation` query system totals, then insert the record outside any transaction that includes the payment ledger. A payment or refund recorded between the sum and the insert persists a `system_*_total_cents` snapshot that does not match the ledger at commit time — audit trail shows a closing/recon against stale data.
- Evidence: `storeDailyClosing()` lines 99–113: `systemCashTotalCentsForDate()` then `DailyCashClosing::create()`. `storeMobileMoneyReconciliation()` lines 128–149: `systemTotalsForDateAndProvider()` then create. Neither wraps sum + insert in `DB::transaction` with consistent read.
- Suggested fix: Wrap sum + insert in a transaction; optionally re-query totals immediately before insert. Document that closings are point-in-time snapshots if intentional.

### R5 — Mass-assignable server-authoritative money fields on closing/recon models
- Severity: Medium
- Status: fixed
- Path: `app/Models/DailyCashClosing.php`
- Area: security
- Finding: `DailyCashClosing` and `MobileMoneyReconciliation` mark ledger fields (`system_cash_total_cents`, `system_total_cents`, `difference_cents`, `transaction_count`) as `#[Fillable]`. Current controllers set these explicitly, but any future `create($request->validated())` or mass assignment would allow forgery of system totals — contrary to GOAL note to follow `PaymentProcessor` / FormRequest patterns for money.
- Evidence: `DailyCashClosing` Fillable lines 27–36 include `system_cash_total_cents`, `difference_cents`. `MobileMoneyReconciliation` Fillable lines 32–44 include `system_total_cents`, `transaction_count`, `difference_cents`. Controllers currently bypass this risk.
- Suggested fix: Remove server-computed fields from `$fillable`; set only via explicit attributes or guarded `$guarded`. Align with how payment amounts are handled elsewhere.

### R6 — Extra decimal places truncated silently (no validation reject)
- Severity: Medium
- Status: fixed
- Path: `app/Concerns/ConvertsDollarAmounts.php`
- Area: guidelines
- Finding: `dollarsToCents()` truncates fractional input to two digits via `substr($fraction, 0, 2)` without error. Laravel `numeric` validation accepts values like `10.999`, which become 1099¢ instead of 1100¢ — silent money loss vs user intent.
- Evidence: `ConvertsDollarAmounts` lines 21–24. Used by `StoreExpenseRequest`, `StoreDailyCashClosingRequest`, `StoreMobileMoneyReconciliationRequest`, `StorePaymentPlanRequest` with `numeric` rules only (no `decimal:0,2` or regex).
- Suggested fix: Add validation rule `decimal:0,2` (or regex) on all dollar amount fields; reject excess precision before conversion.

### R7 — Incomplete 403 matrix for dentist/receptionist on G13 POST endpoints
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/FinanceExtrasTest.php`
- Area: guidelines
- Finding: Feature tests assert dentist 403 for expenses index, store, and daily close only. No tests for dentist (or receptionist/nurse/lab) POST to mobile-money reconciliation, payment-plans, or insurance-claims — the main “dentist 403 holes” called out in review scope.
- Evidence: `FinanceExtrasTest.php` dentist tests at lines 67–88 and 131–141. Policies restrict MM recon and plans to Admin/Accountant (`ExpensePolicy`, `MobileMoneyReconciliationPolicy`, `PaymentPlanPolicy`). No receptionist 403 tests despite receptionist having billing access.
- Suggested fix: Add feature tests: dentist and receptionist POST to `expenses.mobile-money-reconciliations.store`, `billing.payment-plans.store`, `billing.insurance-claims.store` → 403; assert no DB rows.

### R8 — No test for duplicate daily close or concurrent unique violation handling
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/FinanceExtrasTest.php`
- Area: guidelines
- Finding: Duplicate same-day close is prevented by `Rule::unique` on `closing_date` and DB unique index (migration line 26), but there is no feature test asserting the second POST returns validation error and leaves one row. Concurrent double-submit behavior (possible uncaught `UniqueConstraintViolationException` → 500) is also untested.
- Evidence: `StoreDailyCashClosingRequest` lines 26–29 unique rule. Migration `daily_cash_closings.closing_date` unique. `FinanceExtrasTest` happy-path close only (lines 90–128). `ExpenseController::storeDailyClosing` has no retry/catch for unique violations.
- Suggested fix: Feature test: close twice for same date → 422, count = 1. Optionally wrap create in try/catch and map unique violation to validation error (as `PaymentProcessor` does for payment numbers).

## Low

### R9 — Payment plans and insurance claims have no billing UI (HTTP-only)
- Severity: Low
- Status: open
- Path: `resources/js/pages/billing/Show.vue`
- Area: extensibility
- Finding: Payment plan and insurance claim routes exist and pass feature tests via HTTP POST, but there is no Vue form, Wayfinder action, or `data-test` coverage on billing show. Admin/Accountant cannot create plans or claims through the app UI — only via direct requests.
- Evidence: Grep `resources/js` for `payment-plans` / `insurance-claims` returns no matches. `FinanceExtrasTest` POSTs to billing routes; browser tests cover expenses only.
- Suggested fix: If G13 requires operable UI for plans/claims, add billing show forms gated by policy props; otherwise document backend-only stub in GOAL residue.

### R10 — G13 verification bullets still unchecked (docs drift)
- Severity: Low
- Status: open
- Path: `architecture.md`
- Area: guidelines
- Finding: Implementation and 18 passing tests exist, but `architecture.md` §G13 and `.cursor/workflow/GOAL.md` verification checkboxes remain `[ ]`. Tasks T1/T2 are `started` awaiting review — workflow truth lags code state.
- Evidence: `architecture.md` lines 653–659; `GOAL.md` lines 31–35; `tasks.md` T1/T2 evidence notes tests passed, review pending.
- Suggested fix: After Fixer/Verifier closes High findings, sync checkboxes and task evidence on atomic close.

## Question

### R11 — `closing_date` accepts any date via API (UI posts today only)
- Severity: Question
- Status: open
- Path: `app/Http/Requests/StoreDailyCashClosingRequest.php`
- Area: cohesiveness
- Finding: Cash-close validation requires a date and uniqueness but does not restrict to clinic “today”. The Vue form posts `todayClosingDate` via hidden field, but an Accountant can POST a past or future `closing_date` directly — possibly intentional backfill, possibly scope creep.
- Evidence: `StoreDailyCashClosingRequest` rules lines 26–30 — `required|date|unique` only. `Index.vue` line 242 hidden input defaults to today but is not server-enforced.
- Suggested fix: Confirm product intent. If closings must be same-day only, add server rule comparing to `Carbon::now('Africa/Mogadishu')->toDateString()`.

## Verify pass notes

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R1–R8 | fixed | fixed | Confirmed in code; orchestrator Sail gate 48 passed |
| R9 | open | deferred | Backlog B24 |
| R10 | open | fixed | Architecture G13 checkboxes synced on atomic close |
| R11 | open | deferred | Backlog B25 |
