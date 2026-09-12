# Workflow CHANGELOG

Per-run fix log for goal-workflow Corrector appends. Architecture goal completions go in root `changelog.md`.

## 2026-09-08 — G17 — correct pass

### Fixed

- **R1** — `PatientPicker` no longer emits empty `patient_id` on each keystroke; treatments/imaging create watches ignore empty-to-empty (same as lab) (`PatientPicker.vue`, `treatments/Create.vue`, `imaging/Create.vue`)
- **R2** — Browser lab create types slowly and asserts `patient_id` is absent from the query string until a patient is selected (`tests/Browser/LabOrderTest.php`)
- **R3** — Search `onSuccess` ignores responses that no longer match the current query (`PatientPicker.vue`)

### Deferred / wontfix

- **R4**, **R5**, **R6** — Low; not requested this run (Verifier → Backlog)

### Notes

- Tasks touched: T1
- Tests / checks run: `./vendor/bin/sail artisan test --compact tests/Feature/PatientTest.php tests/Feature/LabOrderTest.php tests/Browser/LabOrderTest.php tests/Browser/TreatmentTest.php tests/Browser/ImagingOrderTest.php` — 63 passed after `sail npm run build`
