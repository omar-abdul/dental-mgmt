# Review results — G4 Treatments and prescriptions

- Date: 2026-09-03
- Mode: verify
- Scope: `app/Http/Controllers/TreatmentController.php`, `app/Http/Requests/StoreTreatmentRequest.php`, `app/Policies/TreatmentPolicy.php`, `app/Concerns/AppointmentValidationRules.php`, `app/Services/PrescriptionNumberGenerator.php`, `app/Http/Controllers/PatientController.php` (treatment history payload), `routes/web.php` (treatment routes), `resources/js/pages/treatments/{Index,Create,Show}.vue`, `resources/js/pages/patients/Show.vue` (history UI), `tests/Feature/TreatmentTest.php`
- Goal: G4 — Treatments and prescriptions

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | High | fixed | `app/Concerns/AppointmentValidationRules.php`, `app/Http/Controllers/TreatmentController.php` | Terminal appointments linkable; complete flips cancelled → completed |
| R2 | Medium | fixed | `app/Http/Requests/StoreTreatmentRequest.php`, `app/Concerns/AppointmentValidationRules.php` | Inactive dentist/fee_item bypass (G3 validation parity gap) |
| R3 | Medium | fixed | `app/Http/Requests/StoreTreatmentRequest.php` | Archived patients can receive new treatments (G3 R2 parity) |
| R4 | Medium | fixed | `tests/Feature/TreatmentTest.php` | Key paths untested: store-as-completed, duplicate appointment_id, view-only GET create |
| R5 | Low | deferred | `app/Http/Controllers/TreatmentController.php` | Create-form patient list hard-capped at 200 rows |

## Assessment overview

- Guidelines: Corrector pass applied G3 validation patterns to the clinical write path. Treatments placeholder replaced with real `TreatmentController`; `Gate::authorize` on all actions; Wayfinder imports; patient show exposes diagnosis/status/date.
- Blast radius: No invoice/payment writes. Appointment completion side-effect guarded for terminal statuses.
- Security: Role matrix enforced. Server-side fee cents; prescriber_id = acting user. R1–R3 validation gaps closed on store path.
- Readability: Follows G2/G3 patterns.
- Extensibility: `PrescriptionNumberGenerator` mirrors G3 lock pattern. R5 patient cap deferred to B15.
- Cohesiveness: Critical allergy flags on create. `completeLinkedAppointment` skips terminal appointments (D4).

## Critical

None.

## High

### R1 — Terminal appointments linkable; complete flips cancelled → completed
- Severity: High
- Status: fixed
- Path: `app/Concerns/AppointmentValidationRules.php`, `app/Http/Requests/StoreTreatmentRequest.php`, `app/Http/Controllers/TreatmentController.php`, `tests/Feature/TreatmentTest.php`
- Area: security
- Finding: Crafted POST could link cancelled/no_show/completed appointments; complete could resurrect vacated slots.
- Evidence (verify): `linkableAppointmentExistsRule()` whitelists live statuses. `completeLinkedAppointment()` returns early for terminal statuses. Pest: cancelled appointment_id → 422.
- Suggested fix: _(applied)_

## Medium

### R2 — Inactive dentist/fee_item bypass (G3 validation parity gap)
- Severity: Medium
- Status: fixed
- Path: `app/Http/Requests/StoreTreatmentRequest.php`, `app/Concerns/AppointmentValidationRules.php`
- Area: guidelines
- Finding: Store accepted inactive dentist/fee_item IDs despite UI filtering active rows.
- Evidence (verify): `activeDentistExistsRule()` and `activeFeeItemExistsRule()`. Pest for inactive dentist/fee item.
- Suggested fix: _(applied)_

### R3 — Archived patients can receive new treatments (G3 R2 parity)
- Severity: Medium
- Status: fixed
- Path: `app/Http/Requests/StoreTreatmentRequest.php`
- Area: guidelines
- Finding: Archived patients accepted on treatment store (D7 read-only violation).
- Evidence (verify): `bookablePatientExistsRule()`. Pest: archived patient → 422.
- Suggested fix: _(applied)_

### R4 — Key paths untested: store-as-completed, duplicate appointment_id, view-only GET create
- Severity: Medium
- Status: fixed
- Path: `tests/Feature/TreatmentTest.php`
- Area: guidelines
- Finding: Missing Pest for store-as-completed, unique `appointment_id`, receptionist/nurse GET create 403.
- Evidence (verify): Tests present. Orchestrator Sail gate: 159 passed.
- Suggested fix: _(applied)_

## Low

### R5 — Create-form patient list hard-capped at 200 rows
- Severity: Low
- Status: deferred
- Path: `app/Http/Controllers/TreatmentController.php`
- Area: extensibility
- Finding: `patientOptions()` limits to 200 patients ordered by name.
- Evidence: `patientOptions()` `->limit(200)`.
- Suggested fix: Defer searchable picker; backlog **B15**.

## Question

None.

## Verify pass notes

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R1 | fixed | fixed | `linkableAppointmentExistsRule` + complete guard |
| R2 | fixed | fixed | Active dentist/fee rules |
| R3 | fixed | fixed | `bookablePatientExistsRule` |
| R4 | fixed | fixed | Extra Pest cases; Sail 159 passed |
| R5 | open | deferred | Backlog B15, Expires 2026-10-03 |

### Test gate (mandatory)

Orchestrator re-ran Sail after Verifier sandbox missed Docker: `./vendor/bin/sail artisan test --compact` — **159 passed** (661 assertions).
