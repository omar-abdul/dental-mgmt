# Goal-workflow artifact templates

Copy these shapes into `.cursor/workflow/`. Keep headings and field names stable so subagents can parse consistently.

---

## `GOAL.md`

```markdown
# GOAL — <short title>

- **Architecture id:** G<n> | n/a
- **Mode:** A | A-light | B
- **Why:** <one line: e.g. “touches bearer session → A” / “seed + docs → A-light”>
- **Started:** YYYY-MM-DD
- **Owner run:** <optional label>

## Summary

<1–3 sentences: what must be true when this run ends>

## Scope in

- …

## Scope out

- …

## Verification (from architecture or user)

- [ ] …
- [ ] …

## Notes

<constraints, links to architecture sections, package-local AGENTS>
```

**Mode cheat sheet:** `A` = full Reviewer→Corrector→Verifier; `A-light` = implement + package tests/lint gate; `B` = review only. See [SKILL.md](SKILL.md) Mode selection.

---

## `tasks.md`

```markdown
# Tasks

> Orchestrator owns status transitions after evidence.
> Status: `pending` | `started` | `blocked` | `completed` | `skipped` | `cancelled`

## Task list

### T1 — <title>

| Field | Value |
|-------|-------|
| **id** | T1 |
| **status** | pending |
| **depends_on** | — |
| **Done when** | <measurable indicator; must be non-empty before implement> |

**Description:** <what to build or change>

**Evidence:** <filled when completing: commands, paths, brief result>

---

### T2 — <title>

| Field | Value |
|-------|-------|
| **id** | T2 |
| **status** | pending |
| **depends_on** | T1 |
| **Done when** | … |

**Description:** …

**Evidence:** …

---

## Legend

| Status | Meaning |
|--------|---------|
| pending | Defined, not started |
| started | In progress |
| blocked | See `.cursor/workflow/progress.md` |
| completed | Done when met; Verifier agrees (or no Critical residue) |
| skipped / cancelled | Requires user acknowledgment |
```

---

## `progress.md`

```markdown
# Workflow progress (incomplete / blocked)

> Residue for **this run** only. Architecture-wide incomplete goals live in root `progress.md`.
> Low/Question leftovers go to **Backlog** with `Expires:` (+30 days default). Drop or `wontfix` after expiry.

## Blocked

| Task id | Blocker | Since | Needed from |
|---------|---------|-------|-------------|
| | | YYYY-MM-DD | user / dep / env |

## Incomplete after verify

| Task id | What’s left | Severity residue | Next step |
|---------|-------------|------------------|-----------|
| | | Critical / High / Medium / Low | |

## Backlog (deferred Low / Question)

> Not in-flight work. Do not copy these into root `progress.md`.

### B1 — <short title>
- Related findings: R…
- Status: deferred
- What's left: …
- Why: Low | Question
- Expires: YYYY-MM-DD
- Updated: YYYY-MM-DD

## Open questions

- [ ] …
```
---

## `review_results.md`

Canonical schema matches `.cursor/skills/reviewer/SKILL.md`. Finding ids are `R1`, `R2`, …. Statuses: `open` | `fixed` | `wontfix` | `deferred`.

```markdown
# Review results — <goal title or scope>

- Date: <YYYY-MM-DD>
- Mode: initial | verify
- Scope: <paths / packages>
- Goal: <G-id or GOAL.md title, or none>

## Summary

| ID | Severity | Status | Path | Title |
|----|----------|--------|------|-------|
| R1 | Critical | open | `apps/...` | Short title |

## Assessment overview

- Guidelines: …
- Blast radius: …
- Security: …
- Readability: …
- Extensibility: …
- Cohesiveness: …

## Critical

### R1 — <title>
- Severity: Critical
- Status: open
- Path: `path/to/file`
- Area: security | guidelines | blast-radius | readability | extensibility | cohesiveness
- Finding: …
- Evidence: …
- Suggested fix: …

## High

_(findings or “None.”)_

## Medium

_(findings or “None.”)_

## Low

_(findings or “None.”)_

## Verify pass notes

> Fill on verify pass only. Re-check prior findings; confirm `fixed` or leave `open` / `deferred` with residual detail in `progress.md`.

| Finding id | Prior status | Verify result | Notes |
|------------|--------------|---------------|-------|
| R1 | fixed | fixed \| still open | |
```

---

## `CHANGELOG.md` (workflow)

```markdown
# Workflow CHANGELOG

> Per-run fix log. Corrector appends. Root `changelog.md` records completed architecture goals (G0–G9).

## YYYY-MM-DD — <GOAL / G-id> — correct pass

### Fixed

- **R1** — <one-line what changed> (`path`)
- **R2** — …

### Deferred / wontfix

- **R3** — <reason; user ack if High+>

### Notes

- Tasks touched: T…
- Tests / checks run: …
```

Empty seed file may contain only the title and a one-line purpose until the first correct pass:

```markdown
# Workflow CHANGELOG

Per-run fix log for goal-workflow Corrector appends. Architecture goal completions go in root `changelog.md`.
```
