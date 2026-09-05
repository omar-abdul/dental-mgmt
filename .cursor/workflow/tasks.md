# Tasks

> Orchestrator owns status transitions after evidence.

## Task list

### T1 — Provision dentist profile on staff create

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | `staff.store` with Dentist role creates an active `dentists` row linked to the user; other roles create no dentist row |

**Description:** Transaction around user + optional dentist profile.

**Evidence:** `StaffController::store` creates `dentist()` when role is Dentist. `tests/Feature/StaffTest.php` dataset asserts profile vs null.

---

### T2 — Picker tests (dentist, patient, supplier)

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | Feature tests cover every dentist dropdown page, inactive omission, patient search after register, supplier on PO create; browser test sees staff dentist on appointments |

**Description:** HTTP + Playwright.

**Evidence:** `tests/Feature/PickerOptionsTest.php`; `tests/Browser/StaffTest.php` calendar + book form.

---

### T3 — Tests / lint gate

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T1, T2 |
| **Done when** | Narrow Pest files pass; Pint dirty |

**Description:** A-light verifier gate.

**Evidence:** `./vendor/bin/sail artisan test --compact tests/Feature/StaffTest.php tests/Feature/PickerOptionsTest.php tests/Browser/StaffTest.php` — 35 passed. Pint dirty passed.
