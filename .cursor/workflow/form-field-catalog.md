# Frontend form-field catalog (this run)

Backend-sourced fields only. User-typed identity/password/notes fields omitted unless they prefill from props.

Legend: **OK** = query matches UI intent · **BAD** = wrong/missing rows or prefill · **?** = product/architecture ambiguous

## Shared

| Component | Field | Source | Query | Verdict |
|-----------|-------|--------|-------|---------|
| `PatientPicker` | patient search | XHR `patients.search?q=` | `status != Archived`, limit 15 | **BAD** — inactive patients included |
| `PatientPicker` | `patient_id` | selected id (string) | — | ? coercion |

## Auth / settings / dashboard

| Page | Field | Source | Verdict |
|------|-------|--------|---------|
| Login | email, password, remember | none | n/a |
| ForgotPassword | email | none | n/a |
| ResetPassword | email, token | Fortify request | OK |
| ResetPassword | passwordRules | **not passed** by Fortify view | **BAD** |
| ConfirmPassword | password | none | n/a |
| Profile | name, email | `auth.user` | OK |
| Security | passwordRules | `Password::defaults()` | OK |
| Appearance | theme | localStorage only | OK |
| Staff | role options | `ClinicRole::cases()` | OK |
| Staff | list | all users | OK (no staff archive) |
| NotificationTemplates | body | `communication_templates` | OK |
| Dashboard | — | no forms | n/a |
| Placeholder | — | no forms | n/a |

## Patients / appointments / treatments

| Page | Field | Source | Verdict |
|------|-------|--------|---------|
| patients/Index | search | GET + `withTrashed` list | OK |
| patients/Create | gender | `Gender::cases()` | OK |
| patients/Edit | identity fields | `patient` detail | OK |
| patients/Edit | allergies/conditions/meds | nested labels only | **BAD** — `is_critical` not in form; sync → false |
| appointments | date | query + working hours | OK |
| appointments | dentists | active `dentists` | OK |
| appointments | chairs | active chairs | ? rooms.is_active |
| appointments | feeItems | active `fee_items` | OK UI; store `exists` without `is_active` |
| appointments book/edit | patient | PatientPicker | **BAD** inactive |
| appointments edit | duration_minutes | not prefilled | **BAD** empty POST can rewrite length |
| treatments create | dentists, feeItems, statuses | active / enum | OK |
| treatments create | appointments | not cancelled/no-show, no treatment | **BAD** includes Completed; validate excludes |
| treatments create | patient | PatientPicker + `?patient_id=` find | **BAD** inactive; find no status filter |

## Billing / expenses / reports

| Page | Field | Source | Verdict |
|------|-------|--------|---------|
| billing/Index | search | invoice # / patient | OK |
| billing/Show | amount prefill | `balance_cents/100` | OK |
| billing/Show | paymentMethods, MM providers, verification | enums | OK |
| billing/Show | refund payment picker | client filter `status===completed` | ? refunded originals stay completed |
| expenses | categories | hardcoded 4 strings | ? no enum |
| expenses | cash close / MM | server system totals; user counted/provider | OK |
| reports * | from/to | `ReportDateRange` | **BAD** on Outstanding, Inventory stock, Low stock (query ignores dates) |
| Daily appts / registrations / payments / treatment stats | from/to | used in query; dentist self-scope | OK |
| Receipt | — | no forms | n/a |
| Payment plans / claims | — | HTTP-only (B24) | n/a |

## Inventory

| Page | Field | Source | Verdict |
|------|-------|--------|---------|
| inventory | categories, movement types | enums | OK |
| inventory consume | batches | qty>0, **no expiry filter** | **BAD** (B23) |
| PO create | suppliers, items | all rows (no inactive column) | OK |
| PO create | line expiry_date | none prefill; server required | **BAD** UI not required |
| PO receive | no fields | all lines full qty | OK |
| suppliers | create fields | none | n/a (user-typed) |

## Chart / lab / imaging

| Page | Field | Source | Verdict |
|------|-------|--------|---------|
| chart Index | — | no forms | n/a |
| PatientChart odontogram | status/surfaces/FDI | `ToothStatus`/`ToothSurface`/FDI list | OK (= dental_chart) |
| PatientChart plan | dentists | active | OK |
| PatientChart plan item | feeItems prop | active fee catalog **unused in UI** | **BAD** |
| PatientChart plan item | tooth_fdi | free text max:2 | ? B18 |
| encounters SOAP | SOAP fields | existing note | OK |
| lab/imaging create | dentists | active | OK UI; validate no is_active |
| lab/imaging | patient | PatientPicker + find | **BAD**/? archived prefill |
| lab | treatment/encounter options | patient-scoped | OK **UI**; validate not scoped |
| imaging | encounter options | patient-scoped | OK **UI**; validate not scoped |
| imaging | types/statuses | enums; all statuses at create | ? |
| lab Show | status buttons | `allowedTransitions()` | OK |
| imaging Show | — | no forms | n/a |
