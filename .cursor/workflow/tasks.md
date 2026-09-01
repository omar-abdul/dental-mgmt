# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Author architecture.md

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | Root `architecture.md` has product/stack, §3 Current state, settled decisions, domain model, role matrix, UI map, and G0–G9 with Mode + unchecked verify bullets |

**Description:** Turn the thesis modules (§4.5–4.8), screenshots, and current Laravel Vue starter into a buildable architecture with sequenced goals.

**Evidence:** `architecture.md` — G0–G9 present; §3 Current state = starter kit only; D1 retire teams; Mode A on auth/schema/PHI/money goals.

---

### T2 — Canonical example dataset

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | `database/data/golden-smile.example.json` exists and includes staff (Admin/Dentist/Receptionist), named patients (Maria Santos and calendar patients), rooms/dentists, today’s appointments, inventory rows matching the three visible SKUs plus generate rules for dashboard KPIs |

**Evidence:** `database/data/golden-smile.example.json` — `php -r json_decode` OK; staff emails; Maria Santos; 8 named appointments; gloves/composite/anesthetic; `kpis` + `generate`.

---

### T3 — Repo bookkeeping

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T1, T2 |
| **Done when** | Root `changelog.md` has an authorship entry; root `progress.md` has no in-flight G-id; workflow residue files exist |

**Description:** Close this scaffolding run per goal-workflow atomic close (docs only; G0–G9 verify boxes stay unchecked).

**Evidence:** `changelog.md` 2026-09-01 authorship entry; `progress.md` next = G0; `.cursor/workflow/progress.md` open question for missing billing screenshot.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
