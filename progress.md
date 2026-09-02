# Progress

In-flight architecture goals only. Completed G-ids live in `changelog.md`. Per-run review residue lives in `.cursor/workflow/progress.md`.

## G0 — Clinic foundation

- **Mode:** A (session routing, privileged Admin, six roles)
- **Started:** 2026-09-02
- **Status:** in flight — T1–T4 implemented; T5 blocked until PHP 8.4+/Sail runs Feature tests
- **Next:** run `./vendor/bin/sail artisan test --compact`, then atomic-close G0 and start G1
