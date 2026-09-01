# GOAL — Architecture authorship (Golden Smile)

- **Architecture id:** n/a (defines G0–G9; does not implement them)
- **Mode:** A-light
- **Why:** scaffolding docs + example dataset only; no product HTTP/auth/schema change
- **Started:** 2026-09-01
- **Owner run:** architecture-author

## Summary

Author root `architecture.md` for Golden Smile Dental Clinic MIS, sequence implementation as G0–G9 with measurable verify bullets, and commit a canonical example dataset derived from the thesis screenshots (user-mentioned JSON was not attached).

## Scope in

- Root `architecture.md` (product, stack, current state, decisions, domain, authz, UI, goals)
- `database/data/golden-smile.example.json` as the seed/reference dataset
- Root `progress.md` and `changelog.md` bookkeeping
- This run’s `.cursor/workflow/*` artifacts

## Scope out

- Product code, migrations, Vue pages, Fortify/team changes
- Implementing any G-id
- Git commit/PR

## Verification (from architecture or user)

- [x] `architecture.md` exists with §3 Current state and G0–G9, each with unchecked verify bullets and a Mode
- [x] Goals are sequenced so G(n+1) does not start before G(n)
- [x] Example JSON covers staff roles, named patients, appointments, inventory, billing, and dashboard KPIs from the screenshots
- [x] Root `changelog.md` records architecture authorship; root `progress.md` has no in-flight G-id

## Notes

- Starter is Laravel Vue starter kit (Fortify + Inertia v3 + teams). Architecture must decide how that maps onto a single-clinic, role-based MIS.
- Screenshot set: login, dashboard, patient registration, appointment calendar, inventory. Billing (Figure 7) was described in text only.
- User said a JSON example dataset would be provided; it was not in the message. Dataset is screenshot-derived and replaceable.
