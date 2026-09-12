# Review results — G17 Lab order patient picker

- Date: 2026-09-08
- Mode: verify
- Scope: `PatientPicker.vue`, `lab/Create.vue`, `PatientPolicy.php`, `PatientController.php`, `LabOrderController.php`, `PatientTest.php`, `LabOrderTest.php`, `Browser/LabOrderTest.php`
- Goal: G17 — Lab order patient picker (Mode A)

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | Medium | fixed | `resources/js/components/PatientPicker.vue` | Keystroke `''` emit fix is page-local; sibling create pages still Inertia-visit |
| R2 | Medium | fixed | `tests/Browser/LabOrderTest.php` | No regression test for “no Inertia visit on keystroke” Done-when |
| R3 | Medium | fixed | `resources/js/components/PatientPicker.vue` | Overlapping search XHRs can apply stale results |
| R4 | Low | deferred | `architecture.md` | Auth matrix omits Lab `patients.search` capability |
| R5 | Low | deferred | `app/Http/Controllers/LabOrderController.php` | Duplicated patient picker payload shaping |
| R6 | Low | deferred | `resources/js/components/PatientPicker.vue` | Label loses `for` when a patient is selected |

## Assessment overview

- Guidelines: Core G17 Done-when items appear implemented — visible combobox, Lab `search` policy (not `viewAny`), phone on prefill, feature + browser happy path. Residual gaps: no automated guard for keystroke Inertia visits (R2).
- Blast radius: Shared `PatientPicker` label fix benefits all consumers. Watch guard applied only on `lab/Create.vue`; treatments/imaging create pages retain reload-on-keystroke if empty emits continue (R1).
- Security: `Gate::authorize('search', Patient::class)` + `SEARCH_ROLES` matches intent. Lab still 403 on patients index/show. Accountant still 403 on search.
- Readability: Lab create watch guard is clear. Policy `SEARCH_ROLES` documents intent. Minor duplication between controllers (R5).
- Extensibility: Empty v-model emits during typing should be fixed in `PatientPicker` (R1).
- Cohesiveness: Architecture §6 matrix not updated for Lab search (R4).

## Critical

None.

## High

None.

## Medium

### R1 — Keystroke `''` emit fix is page-local; sibling create pages still Inertia-visit
- Severity: Medium
- Status: fixed
- Path: `resources/js/components/PatientPicker.vue`, `resources/js/pages/treatments/Create.vue`, `resources/js/pages/imaging/Create.vue`
- Area: extensibility
- Finding: `PatientPicker.onInput` still emits `update:modelValue('')` on every keystroke while searching. Only `lab/Create.vue` guards the parent `watch` against empty-to-empty transitions. `treatments/Create.vue` and `imaging/Create.vue` still call `router.get(...)` on each emit, causing unnecessary Inertia partial reloads during typing — the same bug G17 root-caused on lab create.
- Evidence: `PatientPicker.vue` emits `''` on every input change. `lab/Create.vue` skips visit when `patientId === '' && previousPatientId` is empty. `treatments/Create.vue` and `imaging/Create.vue` have unguarded watches.
- Suggested fix: Stop emitting `update:modelValue('')` during typing in `PatientPicker` (only emit on select/clear).

### R2 — No regression test for “no Inertia visit on keystroke” Done-when
- Severity: Medium
- Status: fixed
- Path: `tests/Browser/LabOrderTest.php`
- Area: guidelines
- Finding: GOAL/T1 Done-when requires that Inertia does not visit `lab.create` on each keystroke. Existing browser test types into the picker and creates an order but does not assert visit count or absence of intermediate `lab.create` requests while typing.
- Evidence: Browser create test fills search and selects; no query-string or typed-value assertions during typing.
- Suggested fix: Type slowly into `@patient-picker-search` and assert `patient_id` is absent from the query string and the typed query remains visible until a patient is selected.

### R3 — Overlapping search XHRs can apply stale results
- Severity: Medium
- Status: fixed
- Path: `resources/js/components/PatientPicker.vue`
- Area: readability
- Finding: Debounced search fires `http.get` without aborting prior in-flight requests. If responses return out of order, the dropdown can show results for an older query.
- Evidence: `scheduleSearch` / `runSearch` call `http.get` with no request id, abort signal, or “latest query wins” check in `onSuccess`.
- Suggested fix: In `onSuccess`, ignore payloads whose query does not match the current `query` ref.

## Low

### R4 — Auth matrix omits Lab `patients.search` capability
- Severity: Low
- Status: deferred
- Path: `architecture.md`
- Area: guidelines
- Finding: G17 adds `PatientPolicy::search` for Lab. Architecture §6 capability table still shows Lab with no patient access.
- Evidence: `architecture.md` Patients view — for Lab. G17 verify bullets describe picker search.
- Suggested fix: Add a “Patient picker search (`patients.search`)” row with Lab ✓; Patients module remains — for Lab.

### R5 — Duplicated patient picker payload shaping
- Severity: Low
- Status: deferred
- Path: `app/Http/Controllers/LabOrderController.php`, `app/Http/Controllers/PatientController.php`
- Area: cohesiveness
- Finding: Identical picker payload mapping exists in two private methods.
- Evidence: `PatientController::patientSearchResult` and `LabOrderController::patientPickerPayload`.
- Suggested fix: Extract a shared helper on `Patient` or a DTO used by both controllers.

### R6 — Label loses `for` when a patient is selected
- Severity: Low
- Status: deferred
- Path: `resources/js/components/PatientPicker.vue`
- Area: readability
- Finding: When `hasSelection` is true, the label has no `for`, so clicking “Patient” does not focus a control.
- Evidence: `:for="hasSelection ? undefined : searchInputId"`.
- Suggested fix: Associate the label with the selected chip or Clear button.

## Verify pass notes

> Fill on verify pass only. Re-check prior findings; confirm `fixed` or leave `open` / `deferred` with residual detail in `progress.md`.

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R1 | fixed | fixed | No empty emit on keystroke; treatments/imaging empty-to-empty guard |
| R2 | fixed | fixed | `typeSlowly` + `assertQueryStringMissing('patient_id')` |
| R3 | fixed | fixed | `onSuccess` ignores query mismatch |
| R4 | open | deferred | Backlog B28 |
| R5 | open | deferred | Backlog B29 |
| R6 | open | deferred | Backlog B30 |

