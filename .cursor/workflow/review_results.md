# Review results — G5 Billing and payments

- Date: 2026-09-03
- Mode: verify
- Scope: `app/Http/Controllers/BillingController.php`, `app/Http/Requests/StorePaymentRequest.php`, `app/Http/Requests/StoreRefundRequest.php`, `app/Policies/InvoicePolicy.php`, `app/Services/InvoiceGenerator.php`, `app/Services/PaymentProcessor.php`, `app/Concerns/ConvertsDollarAmounts.php`, `routes/web.php`, `resources/js/pages/billing/{Index,Show,Receipt}.vue`, `tests/Feature/BillingTest.php`
- Goal: G5 — Billing and payments

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | Critical | fixed | `app/Services/PaymentProcessor.php` | Concurrent refunds corrupt `amount_paid_cents` |
| R2 | High | fixed | `app/Services/PaymentProcessor.php` | Same original payment refundable repeatedly |
| R3 | Medium | fixed | `app/Http/Requests/StorePaymentRequest.php` | Float dollar→cents conversion (D11) |
| R4 | Medium | fixed | `app/Http/Controllers/BillingController.php` | `pay`/`refund` omit controller `Gate::authorize` |
| R5 | Medium | fixed | `tests/Feature/BillingTest.php` | Missing dentist pay 403 and refund-bound tests |
| R6 | Low | deferred | `app/Services/InvoiceGenerator.php` | Invoice + line items not in one DB transaction |

## Assessment overview

- Guidelines: Policy roles match GOAL. `pay`/`refund` call `Gate::authorize`. Integer-safe `ConvertsDollarAmounts`. Invoice totals server-computed.
- Blast radius: No invoice delete routes; financial FKs use `restrictOnDelete`. Refund integrity under invoice row lock.
- Security: Role matrix enforced; MM pending does not apply balance or issue receipt.
- Readability: Centralized `PaymentProcessor`; Wayfinder imports in billing Vue pages.
- Extensibility: Per-payment cumulative refund cap; number generators follow G3/G4 pattern.
- Cohesiveness: Matches §5.2 / D11 / D12. R6 deferred (Low).

## Critical

### R1 — Concurrent refunds corrupt `amount_paid_cents`
- Severity: Critical
- Status: fixed
- Path: `app/Services/PaymentProcessor.php`
- Area: security
- Finding: Refund amount validation ran on stale invoice state before `lockForUpdate`.
- Evidence: Validated inside transaction after lock; sequential over-refund test.
- Suggested fix: _(applied)_

## High

### R2 — Same original payment refundable repeatedly
- Severity: High
- Status: fixed
- Path: `app/Services/PaymentProcessor.php`
- Area: cohesiveness
- Finding: No cumulative cap per original payment.
- Evidence: SUM of refunded rows by `reference_number`; double-refund test.
- Suggested fix: _(applied)_

## Medium

### R3 — Float dollar→cents conversion (D11)
- Severity: Medium
- Status: fixed
- Path: `app/Http/Requests/StorePaymentRequest.php`
- Area: guidelines
- Finding: Float multiply/round for cents.
- Evidence: `ConvertsDollarAmounts::dollarsToCents()` string split.
- Suggested fix: _(applied)_

### R4 — `pay`/`refund` omit controller `Gate::authorize`
- Severity: Medium
- Status: fixed
- Path: `app/Http/Controllers/BillingController.php`
- Area: guidelines
- Finding: Mutating actions lacked controller-level authorize.
- Evidence: Controller now authorizes pay/refund.
- Suggested fix: _(applied)_

### R5 — Missing dentist pay 403 and refund-bound tests
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/BillingTest.php`
- Area: extensibility
- Finding: Incomplete role/refund test matrix.
- Evidence: BillingTest includes dentist pay 403, refund bounds, double-refund.
- Suggested fix: _(applied)_

## Low

### R6 — Invoice + line items not in one DB transaction
- Severity: Low
- Status: deferred
- Path: `app/Services/InvoiceGenerator.php`
- Area: blast-radius
- Finding: Mid-loop failure could leave partial line items.
- Evidence: `createInvoice()` without wrapping `DB::transaction`.
- Suggested fix: Wrap in transaction. Backlog **B16**.

## Verify pass notes

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R1 | fixed | fixed | Locked refund validations |
| R2 | fixed | fixed | Cumulative refund SUM |
| R3 | fixed | fixed | ConvertsDollarAmounts trait |
| R4 | fixed | fixed | Gate::authorize on pay/refund |
| R5 | fixed | fixed | Expanded BillingTest |
| R6 | open | deferred | Backlog B16, Expires 2026-10-03 |

### Test gate (mandatory)

Orchestrator re-ran Sail after Verifier sandbox missed Docker: `./vendor/bin/sail artisan test --compact` — **183 passed** (796 assertions).
