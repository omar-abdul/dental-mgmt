# Workflow progress (incomplete / blocked)

> Residue for **this run** only. Architecture-wide incomplete goals live in root `progress.md`.
> Low/Question leftovers go to **Backlog** with `Expires:` (+30 days default). Drop or `wontfix` after expiry.

## Blocked

| Task id | Blocker | Since | Needed from |
|---------|---------|-------|-------------|
| | | YYYY-MM-DD | user / dep / env |

## Incomplete after verify

| Task id | What’s left | Severity residue | Next step |
|---------|-------------|------------------|-----------|
| | | Critical / High / Medium / Low | |

## Backlog (deferred Low / Question)

> Not in-flight work. Do not copy these into root `progress.md`.

### B1 — Billing screenshot (Figure 7)
- Related findings: —
- Status: deferred
- What's left: No Figure 7 image; G5 UI inferred from DCMS billing + thesis text
- Why: Question
- Expires: 2026-10-02
- Updated: 2026-09-02

### B2 — Receptionist Treatments view (R1)
- Related findings: R1
- Status: deferred
- What's left: §6 write row vs G4 view-only; G0 GET placeholder includes Receptionist. Split view vs write before G4 mutations.
- Why: Question
- Expires: 2026-10-02
- Updated: 2026-09-02

### B3 — Unused StaffController Request params (R7)
- Related findings: R7
- Status: deferred
- What's left: `index`/`create` unused `$request` may trip PHPStan level 7
- Why: Low
- Expires: 2026-10-02
- Updated: 2026-09-02

### B4 — Orphan 2FA artifacts (R8)
- Related findings: R8
- Status: deferred
- What's left: `TwoFactorAuthenticationRequest`, `TwoFactorLoginResponse`, `UserFactory::withTwoFactor()` unused
- Why: Low
- Expires: 2026-10-02
- Updated: 2026-09-02

### B5 — Sole Admin can self-delete (R9)
- Related findings: R9
- Status: deferred
- What's left: Confirm whether profile destroy should be blocked for last Admin
- Why: Question
- Expires: 2026-10-02
- Updated: 2026-09-02

### B6 — Invoice cent totals mass-assignable (R9)
- Related findings: R9
- Status: deferred
- What's left: Narrow Invoice fillable / compute totals in a billing action when G5 ships
- Why: Low
- Expires: 2026-10-02
- Updated: 2026-09-02

### B7 — FeeItemSeeder tax_rate_bps untested (R10)
- Related findings: R10
- Status: deferred
- What's left: Document JSON `tax_rate` semantics; unit test non-zero rates when DCMS adds taxable fees
- Why: Low
- Expires: 2026-10-02
- Updated: 2026-09-02

### B8 — Patient show audit test omits ip (R7)
- Related findings: R7
- Status: deferred
- What's left: Assert `ip` on `patient.viewed` audit rows in PatientTest
- Why: Low
- Expires: 2026-10-02
- Updated: 2026-09-02

### B9 — is_critical toggles absent from patient forms (R8)
- Related findings: R8
- Status: deferred
- What's left: Expose critical flags on allergy/condition/med forms, or drop unused validation until G4
- Why: Low
- Expires: 2026-10-02
- Updated: 2026-09-02

### B10 — Index patient access audit unresolved (R9)
- Related findings: R9
- Status: deferred
- What's left: Architecture says show/index count as access; G2 only logs show. Confirm whether index/search should write audit_logs
- Why: Question
- Expires: 2026-10-02
- Updated: 2026-09-02

### B11 — Calendar cards open edit without can_update (G3 R7)
- Related findings: R7
- Status: deferred
- What's left: Gate calendar card click on `can_update` or show a read-only detail for Dentist/Nurse
- Why: Low
- Expires: 2026-10-03
- Updated: 2026-09-03

### B12 — Calendar card left color bar (G3 R8)
- Related findings: R8
- Status: deferred
- What's left: D9 screenshot parity — left color bar instead of full-card `backgroundColor`
- Why: Low
- Expires: 2026-10-03
- Updated: 2026-09-03

### B14 — Completed appointments block same-slot rebooking (G3 R10)
- Related findings: R10
- Status: deferred
- What's left: Product call recorded: `completed` continues to occupy the slot (D4 does not vacate completed). Revisit only if clinic wants same-day rebook after a finished visit.
- Why: Question
- Expires: 2026-10-03
- Updated: 2026-09-03

### B16 — Invoice generate transaction wrap (G5 R6)
- Related findings: R6
- Status: deferred
- What's left: Wrap `InvoiceGenerator::createInvoice()` invoice + line items in `DB::transaction()` to avoid partial invoices on mid-loop failure
- Why: Low
- Expires: 2026-10-03
- Updated: 2026-09-03

## Open questions

- [ ] Confirm Receptionist should see Treatments nav in G0 (view-only) vs hide until G4
- [ ] Confirm staff profile self-delete is allowed
- [ ] Confirm whether patient index/search should write audit logs (B10)
