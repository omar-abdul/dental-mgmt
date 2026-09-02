# GOAL — Treatments and prescriptions

- **Architecture id:** G4
- **Mode:** A
- **Why:** clinical write path
- **Started:** 2026-09-03
- **Closed:** 2026-09-03
- **Owner run:** g4-treatments-prescriptions

## Summary

Replace the treatments placeholder so Dentist/Admin can record diagnosis, fee-item procedures, and a prescription (prescriber = logged-in user). Completing a treatment may set the linked appointment to `completed`. Patient show lists treatment history. Nurse views, cannot POST Rx. Receptionist is view-only. Critical allergy flags are visible on the treatment form.

**Closed.** T1–T3 completed. Mode A: R1 High + R2–R4 Medium fixed; R5 deferred to B15. Sail test gate 159 passed.

## Scope in

- Inertia treatments index (keep route name `treatments.index`) plus create/show as needed
- `TreatmentPolicy`: view Admin/Dentist/Receptionist/Nurse; write (create/update/complete/Rx) Admin/Dentist only
- Diagnosis, procedures (`fee_items`, optional `tooth_fdi`, quantity; `fee_cents` from fee catalog × quantity, integer cents)
- Prescription + items; `prescriber_id` is the authenticated user; sequential `RX-{YYYY}-{#####}` with lock (match G2/G3 generators)
- Completing treatment with `appointment_id` may set that appointment `completed`
- Patient show treatment history (not just `#id`)
- Critical allergy (`is_critical`) flags visible on the treatment form
- Pest: create treatment+rx, history on patient show, 403s (Nurse POST Rx, Receptionist mutate, Accountant/Lab GET)
- Wayfinder Vue imports (`@/actions`, `@/routes`)
- Keep Receptionist GET `treatments.index` 200 (`PlaceholderModuleTest`)

## Scope out

- Invoices / payments (G5)
- Odontogram / chart lock (G10)
- Lab orders (G11)
- G9 named demo treatments
- REST `/api/v1`, new Composer/npm packages
- Do not hide Receptionist Treatments nav (B2: view-only; mutations 403)

## Verification (from architecture or user)

- [x] Dentist/Admin record diagnosis, procedures (fee_items), prescription + items; prescriber is the user
- [x] Completing treatment may set appointment `completed`
- [x] Patient show lists history; Nurse can view, not POST Rx; Receptionist view-only
- [x] Critical allergy flags visible on the treatment form
- [x] Feature tests: create treatment+rx, history, 403s

## Notes

- Architecture §5.2 treatments/prescriptions, §6 Treatments/Rx write row, D11 cents. Empty Controller → `Gate::authorize`.
- Host PHP 8.3; tests: `./vendor/bin/sail artisan test --compact`. Do **not** `migrate:fresh` without `--seed`.
- Prior Backlog B1–B14 remain; G4 residue B15 expires 2026-10-03.
