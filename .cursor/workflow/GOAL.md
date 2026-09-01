# GOAL — Ingest DCMS JSON into architecture

- **Architecture id:** n/a (revises G0–G15; does not implement)
- **Mode:** A-light
- **Why:** scaffolding docs + dataset only; no product HTTP/auth/schema change
- **Started:** 2026-09-02
- **Owner run:** dcms-json-ingest

## Summary

Persist the user-supplied DCMS JSON as the domain contract, reconcile it with Golden Smile UI screenshots, and revise `architecture.md` goals so Wave 1 (G0–G9) is still a shippable clinic and Wave 2 (G10–G15) covers JSON modules the thesis screens omitted.

## Scope in

- `database/data/dcms.json` (verbatim contract)
- Revise `architecture.md` decisions, domain, roles, goals
- Align `database/data/golden-smile.example.json` to DCMS field shapes
- Root changelog + progress; workflow residue TTL

## Scope out

- Product code, migrations, Vue pages
- Implementing any G-id
- REST `/api/v1`, SMS gateways, payment-provider APIs, i18n

## Verification (from architecture or user)

- [x] `database/data/dcms.json` parses as JSON and includes roles, fee_items, working_hours, payment_system, required_workflows
- [x] `architecture.md` §3 current state still starter-only; D-decisions prefer JSON for domain and screenshots for the five UI screens
- [x] G0–G9 verify bullets match DCMS (6 roles, USD cents, chairs, Mogadishu hours, mobile-money recording)
- [x] G10–G15 exist for chart/clinical sign-off, lab, inventory-advanced, finance extras, notifications, imaging
- [x] Screenshot demo JSON uses first/last name, USD cents, chairs, and password length ≥ 10

## Notes

- Prior run leftover: Figure 7 billing screenshot missing → Backlog +30d
- JSON currency USD + timezone Africa/Mogadishu override thesis ₱ / Manila
- JSON `money_storage: decimal` is mapped to integer **cents** in Laravel (D11)
