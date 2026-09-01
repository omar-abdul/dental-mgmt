# Changelog

Completed architecture goals (G0–G15) and substantial scaffolding. Per-run Corrector notes live in `.cursor/workflow/CHANGELOG.md`.

## 2026-09-02 — DCMS JSON ingested (not a G-id)

Replaced thesis-only domain assumptions with the user DCMS contract: USD, `Africa/Mogadishu`, six roles, chairs, fee catalog, mobile-money recording rules. Split delivery into Wave 1 (G0–G9 operable clinic) and Wave 2 (G10–G15 JSON depth). Golden Smile remains the UI brand.

- **Verified:** `database/data/dcms.json` parses; `architecture.md` G0–G15 with Mode + verify bullets; screenshot fixture reshaped to DCMS fields
- **Packages:** docs + JSON only (no product code)
- **Next implement:** G0

## 2026-09-01 — Architecture authored (not a G-id)

Defined Golden Smile Dental Clinic MIS on the Laravel Vue starter kit (superseded in domain details by 2026-09-02 ingest).

- **Verified:** initial G0–G9 + screenshot-derived demo JSON
- **Packages:** docs + example JSON only
