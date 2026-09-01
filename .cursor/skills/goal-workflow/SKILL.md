---
name: goal-workflow
description: >-
  Orchestrates goal/feature work through define → implement → review → correct →
  verify, using .cursor/workflow artifacts. Use when the user runs /goal-workflow,
  asks to run the goal workflow, starts an architecture goal (G2+), or needs a
  bounded review-correct loop with review_results.md. Supports Mode A (full),
  A-light (tests-as-verify), and B (review only).
disable-model-invocation: true
---

# Goal workflow

Parent-agent orchestrator for architecture goals and scoped features. You own mode,
task definitions, status, and subagent spawns. Prefer scaffolding docs over product
code; spawn implement/review/correct/verify work as specified below.

## Artifacts (gittracked)

All run state lives under `.cursor/workflow/`:

| File | Role |
|------|------|
| `GOAL.md` | Optional active goal summary; re-read before each major phase |
| `tasks.md` | Task list with ids, Done when, status, depends_on |
| `progress.md` | Incomplete / blocked residue for this run (+ Backlog with TTL) |
| `review_results.md` | Adversarial findings (Reviewer/Verifier write here; Mode A full) |
| `CHANGELOG.md` | Per-run fix log (Corrector appends) |

Exact markdown shapes: [templates.md](templates.md). Task prompts: [subagent-prompts.md](subagent-prompts.md).

### Repo bookkeeping vs workflow

| Concern | Where |
|---------|--------|
| Goal definitions / verification criteria | root `architecture.md` |
| Cross-agent “started but not finished” goals | root `progress.md` |
| Completed goals (G0–G9) + substantial deliverables | root `changelog.md` |
| Per-run tasks, findings, fix log | `.cursor/workflow/*` |

When starting architecture goal implementation, also add/update the root `progress.md` entry. Close is incomplete until the **atomic close checklist** below is done.

**Parity:** User-level `/goal` (`~/.cursor/skills/goal` on Windows and WSL) uses the same Modes / close / residue rules (Fixer naming). Keep both OS copies identical via `sync-windows-wsl.sh`. This project skill uses Corrector naming in prompts; behavior matches.

Do **not** use a root `code_review.md`. Reviews go to `.cursor/workflow/review_results.md`.

## Roles

| Role | Who | Writes product code? | Duty |
|------|-----|----------------------|------|
| **Orchestrator** | Parent running this skill | Scaffolding docs only | Mode, tasks, status, spawns, atomic close |
| **Implementer** | Parent or Task subagent | Yes | Implements against accepted goals / Done when |
| **Reviewer** | Task + `.cursor/skills/reviewer` | No | Writes `review_results.md` (Mode A full; optional on A-light) |
| **Corrector** | Task subagent | Yes | Fixes only listed findings; appends workflow `CHANGELOG.md` |
| **Verifier** | Task + reviewer skill (A) or orchestrator tests gate (A-light) | No | Fresh review or package tests; leftover → workflow `progress.md` |

## Status model

Task statuses (only orchestrator transitions after evidence):

| Status | Meaning |
|--------|---------|
| `pending` | Defined, not started |
| `started` | Active implementation or in-flight correction |
| `blocked` | Waiting on user/decision/dependency — note in `progress.md` |
| `completed` | Done when met and Verifier agrees (or no Critical residue) |
| `skipped` / `cancelled` | User acknowledgment required |

Finding statuses in `review_results.md`: `open` | `fixed` | `wontfix` | `deferred`. Ids are `R1`, `R2`, ….

Severity gate for Corrector:

- **Critical / High** — required fix
- **Medium** — fix if in-scope for the goal
- **Low** — only if user asked

**Skip Corrector** when Reviewer finds zero Critical, High, or Medium-in-scope findings. Still run Verifier (A) or tests gate (A-light).

## Mode selection (orchestrator chooses before tasks)

Record in `GOAL.md` as `A` | `A-light` | `B` with a one-line **Why**.

| Mode | When | Loop |
|------|------|------|
| **A — Full** | Architecture G-ids that touch auth/session, ownership, payments, WS auth, admin privilege, or schema other goals depend on | Implement → Reviewer → Corrector (if needed) → Verifier |
| **A-light** | Maintainability, UX shell, seeds, docs, non-security polish, refactors with unchanged HTTP contracts | Implement → **package tests + lint as Verifier gate** → optional Reviewer only if user asks or Critical risk surfaces |
| **B** | Review-only | Reviewer → ask → Corrector → Verifier once if corrected |

**Default:** if any of auth/session, ownership, money, moderation, realtime security, or privileged admin → **A**. Otherwise prefer **A-light**.

## Gates

1. **No implementation** until every active task has a non-empty **Done when** indicator.
2. Reject vague goals once (ask for measurable Done when); do not invent silent success criteria that contradict `architecture.md`.
3. **Reviewer** never edits `apps/` or `packages/` — only `review_results.md` (+ chat).
4. **Corrector** only fixes findings present in `review_results.md` (no drive-by refactors).
5. Status transitions only via orchestrator after evidence (tests, curl, Verifier marks).
6. Re-read `GOAL.md` (and relevant `architecture.md` goal) before each major phase.
7. Prefer **background Task** subagents for Reviewer / Corrector / Verifier so the parent stays orchestrator.
8. Mode A: one implement → review → correct → verify loop; stop after verify unless Critical findings remain, then **one** more correct+verify. Mode A-light: implement → tests gate; no adversarial loop unless escalated.
9. Do not start G(n+1) until G(n) tasks are completed (or skipped/cancelled with user ack) and Critical findings are closed.
10. No git commit/PR unless the user asked in the goal.
11. **Drift canary:** before starting a G-id, if root `changelog.md` already has a completion entry but `architecture.md` verify bullets for that G-id are unchecked → **sync docs only** (check boxes + current state); do not re-implement.

## Atomic close checklist (orchestrator, same turn)

Close is **incomplete** until all of these land together when the goal/run succeeds:

| Artifact | Action |
|----------|--------|
| `architecture.md` | Check off that G-id’s verify bullets (if architecture goal); bump §3 **Current state** if sequencing or shipped surface changed |
| root `changelog.md` | Append dated entry (summary, verified, packages) |
| root `progress.md` | Remove or clear that goal’s in-flight entry |
| `.cursor/workflow/*` | Apply **residue TTL** (below); reset or leave Backlog only — do not leave a stale active GOAL pretending to be in flight |

Verifier proposes “goal achieved”; **orchestrator** owns this sync.

## Residue TTL (workflow `progress.md`)

- **Critical / High** — must not remain `open` at close.
- **Medium in-scope** — fix or `wontfix` with reason before close.
- **Low / Question** — fix now, or move to **Backlog** with `Expires: YYYY-MM-DD` (default **+30 days**). On expiry without pickup → delete or mark `wontfix`.
- Root `progress.md` = **in-flight goals only**. Deferred review nits do **not** live there.
- At the **start** of each new `/goal`: move prior run’s open Low/Question residue into Backlog (with Expires) or drop expired items — do not append forever.

## Mode A — Goal / feature (full)

1. **Ingest** — user text and/or continuously re-read `.cursor/workflow/GOAL.md`. Map to architecture G-id when applicable. Run drift canary.
2. **Define tasks** — each needs `id`, title, description, **Done when**, `status`, optional `depends_on`. Write `tasks.md` from [templates.md](templates.md). Set Mode **A** + Why in `GOAL.md`.
3. **Start** — set current task(s) to `started`; blockers → workflow `progress.md`. Add root `progress.md` if this is an architecture goal.
4. **Implement** — until Done when indicators are met (parent or implementer Task).
5. **Reviewer** — spawn with prompt from [subagent-prompts.md](subagent-prompts.md); load reviewer skill; write `review_results.md`.
6. **Corrector** — skip if no Critical/High/Medium-in-scope; else Critical/High required; Medium if in-scope; Low only if asked. Append workflow `CHANGELOG.md`.
7. **Verifier** — fresh review scoped to goal + prior findings; update task status; leftover → workflow `progress.md` (Backlog + Expires for Low/Question).
8. If Critical remains → one more correct+verify, then stop and report.
9. When indicators green and Critical closed → **atomic close checklist**.

## Mode A-light — Goal / feature (light)

1. **Ingest** / drift canary — same as Mode A.
2. **Define tasks** with Done when. Set Mode **A-light** + Why in `GOAL.md`.
3. **Start** — root `progress.md` if architecture / cross-agent substantial work.
4. **Implement** until Done when met.
5. **Verifier gate (orchestrator or one Task):** run package `test` (and lint if the package has it) for touched packages; confirm Done when evidence. **No** `review_results.md` unless the user asks or a Critical risk appears mid-run.
6. If tests fail → fix and re-run (do not spawn Reviewer for a red suite alone).
7. Optional escalation: user asks for review, or auth/security/ownership risk discovered → switch remainder to Mode A (Reviewer → Corrector → Verifier).
8. On success → **atomic close checklist**.

## Mode B — Review only

1. Scope from user → spawn **Reviewer** → `review_results.md`.
2. **Ask** before Corrector (or honor “fix Critical/High now”).
3. If corrected: **Verifier** once; update workflow changelog + progress; orchestrator updates task status if tasks exist.

## Spawn sequence

**Mode A:**

```
Define tasks (Done when) → Implement → Reviewer → Corrector? → Verifier
                                    ↑_________________|
                         (only if Critical remain; max one extra loop)
```

**Mode A-light:**

```
Define tasks (Done when) → Implement → tests/lint gate → atomic close
                              (optional escalate to Mode A review loop)
```

Use the copy-paste Task prompts in [subagent-prompts.md](subagent-prompts.md). Tell Reviewer/Verifier to load `.cursor/skills/reviewer/SKILL.md`.

## Hard rules

1. No implementation until every active task has a **Done when** indicator.
2. Reviewer never edits `apps/` / `packages/` — only `review_results.md` (+ chat).
3. Corrector only fixes findings in `review_results.md` (no drive-by refactors).
4. Status transitions only via orchestrator after evidence.
5. Re-read `GOAL.md` before each major phase.
6. Prefer background Task subagents for review/correct/verify so the parent stays orchestrator.
7. Orchestrator picks mode before implementation; Implementer must not invent the mode.
8. Atomic close always includes `architecture.md` checkbox / current-state sync when a G-id ships.

## Out of scope

- Cursor Automations / cron
- Git commit/PR unless the user asks in the goal
- Creating product code outside an active Mode A / A-light run with Done when indicators
