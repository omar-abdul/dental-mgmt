# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Treatments index/show, policy, patient history

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | `treatments.index` is a real list (not PlaceholderModuleController); Dentist/Admin/Nurse/Receptionist GET 200; Accountant and Lab GET 403; guests redirect to login; patient show lists diagnosis, status, date (not only `Treatment #id`); Wayfinder Vue imports work |

**Description:** Replace placeholder treatments route. `TreatmentPolicy` view = Admin, Dentist, Receptionist, Nurse; write = Admin, Dentist. `Gate::authorize`. Expand `PatientController` show treatment payload. Keep `PlaceholderModuleTest` receptionist treatments GET 200.

**Evidence:** `TreatmentController@index`; patient show diagnosis/status/date; Accountant/Lab GET 403. Verifier: no Critical residue. Orchestrator Sail gate: 159 passed.

---

### T2 — Record diagnosis, procedures, Rx; complete appointment

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | Dentist/Admin can create a treatment with diagnosis, ≥1 procedure (fee_item), and prescription items; `prescriber_id` is the acting user; `fee_cents` stored as integer from catalog × quantity (not client-trusted money); completing a treatment linked to an appointment sets that appointment `completed`; Nurse POST Rx 403; Receptionist POST/PUT 403; critical allergy flags appear on the treatment form |

**Description:** Form requests + transaction. Optional `appointment_id` unique. Prescription number `RX-{YYYY}-{#####}` with advisory lock or `lockForUpdate` + retry like G3. Do not create invoices. Surface patient `allergies`/`conditions`/`medications` where `is_critical` on the create/edit form.

**Evidence:** store + complete; prescriber = user; fee_cents from catalog; Nurse/Receptionist POST 403; critical flags on create. R1–R3 remediations verified.

---

### T3 — Pest coverage for G4

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T2 |
| **Done when** | Feature tests cover create treatment+rx (prescriber = user), patient show history, complete-treatment sets appointment completed, Nurse POST Rx 403, Receptionist mutate 403, Accountant GET 403; `./vendor/bin/sail artisan test --compact` green |

**Description:** `tests/Feature/TreatmentTest.php` using `test()`, factories, named assertions. Do not load G9 demo seed.

**Evidence:** `tests/Feature/TreatmentTest.php` (21 cases). Orchestrator: `./vendor/bin/sail artisan test --compact` **159 passed** (661 assertions). Pest HTTP as browser-path substitute.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
