# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Page/form field inventory

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Every Vue page under `resources/js/pages` is listed; each form field that loads backend data has: page, field, control type, source (Inertia prop / XHR / nested record), and the backend query or static enum |

**Description:** Catalog all pages and forms (including dialogs). User-typed-only fields (name, password) are noted as not backend-sourced. Shared components (`PatientPicker`) counted once per usage.

**Evidence:** `.cursor/workflow/form-field-catalog.md`; five module catalogs (patients/appointments/treatments, billing/expenses/reports, inventory, chart/lab/imaging, auth/settings). All 45 Vue pages covered.

---

### T2 — Trace and judge correctness

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | Each backend-sourced field is traced to controller/action; verdict is correct, incorrect, or questionable with evidence (filters, table, scope, money/date shape vs architecture D5/D7/D11/D12) |

**Description:** Compare UI options/defaults to Eloquent queries. Watch inactive dentists, archived patients, expired batches, cancelled appointments, fee catalog vs free text, staff users vs dentist profiles, invoice balances, role-scoped reports.

**Evidence:** Spot-checked controllers/requests vs Vue. Verdicts in catalog + `review_results.md` R1–R12.

---

### T3 — Reviewer findings file

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T1, T2 |
| **Done when** | `.cursor/workflow/review_results.md` is overwritten for this run (Mode: initial) with R-ids for incorrect/risky wiring; catalog summary in Assessment or attached notes; no product code edited |

**Description:** Mode B Reviewer pass. Ask user before Corrector.

**Evidence:** `review_results.md` Mode initial; 0 Critical, 1 High (R1), 6 Medium, 5 Low (R7 deferred B23). No product code edited.

---

### T4 — Correct review findings R1–R12

| Field | Value |
|-------|-------|
| **id** | T4 |
| **status** | completed |
| **depends_on** | T3 |
| **Done when** | Every R1–R12 finding is `fixed` in `review_results.md`; workflow `CHANGELOG.md` has a correct-pass section; narrow Pest covering each changed behavior is green |

**Description:** Corrector: High R1 required; Medium R2–R6, R9 in-scope; Low R7–R8, R10–R12 included because the user asked to fix the errors found. Suggested fixes in `review_results.md`. R5: PatientPicker + bookable exists + prefills = `PatientStatus::Active` only.

**Evidence:** R1–R12 (and R13–R14) `fixed`. Feature gate 244 passed; workflow CHANGELOG correct-pass entries.

---

### T5 — Permanently delete archived patients after confirm

| Field | Value |
|-------|-------|
| **id** | T5 |
| **status** | completed |
| **depends_on** | T3 |
| **Done when** | `DELETE patients.destroy` force-deletes an archived patient with no invoices/payments after a confirmation dialog; non-archived 403; dentist/nurse 403; guest redirect; invoices/payments 422; browser: archive → confirm delete → gone from index and DB |

**Description:** Policy `delete` for WRITE roles only when archived. Show `canDelete`. Dialog pattern like `DeleteUser.vue` / inventory Dialog (`data-test` delete + confirm). D7: do not hard-delete invoices; `restrictOnDelete` on invoices/payments — reject delete if those rows exist. Audit `patient.deleted` then `forceDelete`. Redirect `patients.index`. Keep archive as soft-delete.

**Evidence:** `PatientTest` + `FormAbuseTest` + `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Browser/PatientTest.php` — 3 passed.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
