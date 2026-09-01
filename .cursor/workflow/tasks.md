# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — Persist DCMS JSON

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | completed |
| **depends_on** | — |
| **Done when** | `database/data/dcms.json` exists, `json_decode` succeeds, and it contains `roles`, `billing.fee_items`, `working_hours`, `payment_system`, `required_workflows` |

**Description:** Save the user-provided production schema JSON as the domain contract.

**Evidence:** `database/data/dcms.json` — parse ok, 6 roles, 9 fee_items, working_hours, payment_system, required_workflows present.

---

### T2 — Revise architecture.md

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | completed |
| **depends_on** | T1 |
| **Done when** | `architecture.md` has updated §3–§12: JSON wins domain (USD, Mogadishu, 6 roles, chairs, fees, mobile money); screenshots win the five UI layouts; G0–G9 Wave 1 + G10–G15 Wave 2 with Mode + unchecked verify bullets |

**Description:** Reconcile thesis/screenshots with DCMS JSON. Do not start implementing G0.

**Evidence:** `architecture.md` D1–D14; §9 G0–G15; §3 still starter-only.

---

### T3 — Align screenshot demo JSON

| Field | Value |
|-------|-------|
| **id** | T3 |
| **status** | completed |
| **depends_on** | T2 |
| **Done when** | `database/data/golden-smile.example.json` uses DCMS field names (first/last name, chairs, fee codes, USD cents, 6 staff roles, password ≥ 10 chars) while keeping named screenshot people/SKUs/KPIs |

**Description:** Screenshot named records remain the G9 UI fixture, reshaped to the contract.

**Evidence:** parse ok; `demo_password=password12`; chairs CHAIR-001–003; FEE-001–009; Maria Santos + Ahmed Ali; `stock_value_cents=148200`.

---

### T4 — Bookkeeping

| Field | Value |
|-------|-------|
| **id** | T4 |
| **status** | completed |
| **depends_on** | T2, T3 |
| **Done when** | Root `changelog.md` has a 2026-09-02 ingest entry; root `progress.md` has no in-flight G-id; prior billing-screenshot question is in workflow Backlog with Expires |

**Description:** Atomic close for this docs run. G0–G15 verify boxes stay unchecked.

**Evidence:** changelog 2026-09-02; progress next=G0; workflow Backlog B1 Figure 7.

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
