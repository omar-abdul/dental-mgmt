# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Imaging schema + HTTP

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Imaging orders exist with result metadata and optional stored file; Dentist/Admin can create; Receptionist write is 403 |

**Description:** Migrations, models, factories, policies, controllers, named routes. Laravel disk only. No DICOM parser.

**Evidence:** Feature `ImagingOrderTest` covers Admin/Dentist create (`IMG-YYYY-#####`), optional file + result metadata, receptionist 403. Pint `--dirty` passed.

---

### T2 — Vue Imaging + tests

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | Imaging module in sidebar for authorized roles; feature tests cover create + receptionist 403; browser: dentist creates order with optional file (UI + DB/disk); receptionist cannot write; `./vendor/bin/sail artisan test --compact` green for the new tests |

**Description:** Wayfinder, `data-test`. Storage::fake in tests. Update NavigationTest.

**Evidence:** `PLAYWRIGHT_BROWSERS_PATH=0 ./vendor/bin/sail artisan test --compact tests/Feature/ImagingOrderTest.php tests/Browser/ImagingOrderTest.php tests/Browser/NavigationTest.php` — 20 passed. Browser happy path creates order + result metadata; file upload is HTTP-tested (Pest browser server does not parse multipart).

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
