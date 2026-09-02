# Workflow progress (incomplete / blocked)

> Residue for **this run** only. Architecture-wide incomplete goals live in root `progress.md`.
> Low/Question leftovers go to **Backlog** with `Expires:` (+30 days default). Drop or `wontfix` after expiry.

## Blocked

| Task id | Blocker | Since | Needed from |
|---------|---------|-------|-------------|
| T5 | Host PHP 8.3 cannot boot PHPUnit 12; Docker daemon not running so Sail (PHP 8.5) cannot execute `php artisan test --compact` | 2026-09-02 | env: PHP 8.4+ or `./vendor/bin/sail up` |

## Incomplete after verify

| Task id | What’s left | Severity residue | Next step |
|---------|-------------|------------------|-----------|
| T5 | Run Feature tests on PHP 8.4+/Sail and confirm green | — | User: start Docker Desktop or install PHP 8.5, then `./vendor/bin/sail artisan test --compact` |

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

## Open questions

- [ ] Confirm Receptionist should see Treatments nav in G0 (view-only) vs hide until G4
- [ ] Confirm staff profile self-delete is allowed
