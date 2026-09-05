# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Catalog models, pages, and forms

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | `.cursor/workflow/coverage-matrix.md` lists every `app/Models` class, every `resources/js/pages` Vue page, and every FormRequest/mutation route with expected vs existing coverage |

**Description:** Inventory before writing tests. Nested models (allergies, invoice items) map to parent forms.

**Evidence:** `.cursor/workflow/coverage-matrix.md` — 49 models, 44 routed Vue pages (`modules/Placeholder` unused), all named mutations with expected vs abuse.

---

### T2 — Model persistence tests

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | A Pest feature test creates each of the 49 models (factory, or explicit `create` when no factory) and `assertModelExists` |

**Description:** `tests/Feature/ModelCatalogTest.php`. AppointmentRevision has no factory.

**Evidence:** `./vendor/bin/sail artisan test --compact tests/Feature/ModelCatalogTest.php` — passed (included in 259 Feature coverage tests).

---

### T3 — GET page access + mutation abuse (HTTP)

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | Feature tests hit every named GET page (guest → login, authorized → 200 + Inertia component, forbidden role → 403) and every mutation (guest → login; empty/invalid → validation; XSS/SQLi-ish/overlong/extra keys/wrong types → 422 or stored without privilege escalation / 500) |

**Description:** `HttpPageAccessTest`, `FormAbuseTest`, `AuthAbuseTest`. Search query injection must not 500.

**Evidence:** `./vendor/bin/sail artisan test --compact tests/Feature/ModelCatalogTest.php tests/Feature/HttpPageAccessTest.php tests/Feature/AuthAbuseTest.php tests/Feature/FormAbuseTest.php` — 259 passed. Product fix: `ReportDateRange::parseDay()` falls back on garbage `from`/`to`.

---

### T4 — Browser smoke every page + form abuse

| Field | Value |
|-------|-------|
| **id** | T4 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | Pest browser tests visit every staff GET page as an authorized role (visible heading + no JS errors) and drive patient create with XSS plus login with invalid credentials; DB matches expected persistence/non-persistence |

**Description:** `StaffPagesSmokeTest`, `FormAbuseSmokeTest`. Security settings uses password confirmation; cover confirm-password page in browser and security via HTTP.

**Evidence:** `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Browser/StaffPagesSmokeTest.php tests/Browser/FormAbuseSmokeTest.php` — 49 passed. Product fix: `TreatmentPlanController.updateItem.form([plan.id, item.id])` so admin chart with plan items renders.

---

### T5 — Tests / lint gate

| Field | Value |
|-------|-------|
| **id** | T5 |
| **status** | completed |
| **depends_on** | T2, T3, T4 |
| **Done when** | `./vendor/bin/sail artisan test --compact` passes for the new Feature + Browser files; `vendor/bin/pint --dirty --format agent` if PHP was added |

**Description:** A-light verifier gate.

**Evidence:** Feature 259 passed; Browser 49 passed; `vendor/bin/pint --dirty --format agent` fixed unused imports in `ModelCatalogTest.php` and `FormAbuseTest.php`. Re-ran those two files after Pint — passed.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
