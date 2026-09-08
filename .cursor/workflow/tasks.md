# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Searchable dentist combobox on treatment create

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Treatment create dentist field is a combobox (`role="combobox"`) that lists active dentists, filters by typed label, and submits `dentist_id`. Browser test records a treatment by searching/selecting a dentist (not native `select()`). Feature test: create page includes dentist options; store with that id persists. Empty native dentist `<select>` is gone from `treatments/Create.vue`. |

**Description:** Native dentist `<select>` on `resources/js/pages/treatments/Create.vue` shows an empty control (Vue `:selected` without `v-model`). Replace with a reusable combobox over the existing `dentists` Inertia prop (client-side filter; list is small). Pattern: `PatientPicker` (hidden input + typeahead + keyboard). `data-test`: `dentist-picker-search`, `dentist-picker-option`. Keep `defaultDentistId` as the initial selection when the logged-in user has a dentist profile. No new npm packages.

**Evidence:** Implementer: `LocalCombobox.vue` on treatments/Create; native dentist select removed. `./vendor/bin/sail artisan test --compact tests/Feature/PickerOptionsTest.php` — 10 passed. `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Browser/TreatmentTest.php` — 2 passed. Verifier: Feature gate 320 passed; recommend completed.

---

### T2 — Cascade soft-delete patients (replace archive)

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Admin/Receptionist delete a non-trashed patient via confirm dialog (`DELETE patients.destroy`). Patient and related domain rows are **soft**-deleted in one transaction; patient is gone from index/search/calendar/treatments/billing/lab/imaging/chart indexes and reports. Invoices/payments still exist with `deleted_at` (not force-deleted). Dentist/Nurse 403. Guest redirect. Already-trashed destroy 403. Browser: show → confirm delete → not on index; DB `patients.deleted_at` set. Archive UI/route removed or no longer used. |

**Description:** Replace archive + “permanent delete if no invoices” with one soft-delete. Service (e.g. `PatientSoftDeleter`) in a transaction: lock patient; audit `patient.deleted`; `delete()` patient plus related aggregates that have their own indexes: appointments, treatments, prescriptions, invoices, payments, encounters, lab orders, imaging orders, treatment plans, odontogram teeth, tooth histories, emergency contacts, allergies/conditions/meds. Add `deleted_at` + `SoftDeletes` on those models that lack it. Nested rows only reached through a parent may stay if the parent is trashed. Never `forceDelete` invoices/payments (D7). Redirect `patients.index`. Policy `delete` for WRITE roles on non-trashed patients. Remove archive button/route and update Patient/FormAbuse/HttpPageAccess tests. Duplicate-name warning may still include trashed patients.

**Evidence:** Implementer: `PatientSoftDeleter`, related `SoftDeletes`, archive route/UI removed. Browser PatientTest 2 passed. Verifier: guest destroy 302 to login (R1); cascade + billing omit; Feature gate 320 passed. Recommend completed.

---

### T3 — Admin activate / deactivate staff

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Staff index shows Active/Inactive and an Admin-only toggle. `users.is_active` persists. Inactive users cannot authenticate (`login.store` stays guest). Authenticated inactive users are logged out of web routes. Deactivating a Dentist sets `dentists.is_active` false (and true on reactivate) so they leave dentist pickers. Admin cannot deactivate self (422/403). Non-admin 403 on the mutation. Browser: toggle inactive, badge updates, DB `is_active` false. |

**Description:** Migration `users.is_active` boolean default true. Factory state `inactive()`. Gate `updateStaff` admin-only. PATCH (or resource update) on staff. Fortify reject inactive at login with a clear validation message. Middleware or equivalent for already-authenticated deactivated users. Sync dentist profile `is_active`. Do not add Composer packages.

**Evidence:** Implementer: `users.is_active`, Fortify `authenticateUsing`, `EnsureUserIsActive` via `active` alias on `['auth', 'active']` groups, staff toggle UI. StaffTest + Auth 84 passed. Browser StaffTest 3 passed. Verifier: Feature gate 320 passed; recommend completed.

---

### T4 — Printable reports

| Field | Value |
|-------|-------|
| **id** | T4 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Reports hub and each of the seven Wave 1 report pages have a Print control (`data-test="print-report-button"`) that calls `window.print()`. Sidebar/header and date-filter/back-link chrome use `print:hidden`. Report body remains visible. Browser test: Admin opens a report (e.g. daily appointments or payments), sees the Print button, no JS errors. No new print libraries. |

**Description:** Follow `resources/js/pages/billing/Receipt.vue` (`window.print()`, `print:hidden`). Shared small Vue helper is fine. Hide app chrome on print (`AppSidebar`, `AppSidebarHeader`, toaster). Do not add npm packages.

**Evidence:** Implementer: `ReportPrintButton` on hub + 7 reports; chrome `print:hidden`. Browser ReportsTest 2 passed. Verifier: print feature test (R2) + Feature gate 320 passed. R3 Low hub wrap deferred B27. Recommend completed.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
