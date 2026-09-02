# GOAL — Clinic foundation (auth, shell, retire teams)

- **Architecture id:** G0
- **Mode:** A
- **Why:** session routing, privileged Admin, six clinic roles, Fortify surface
- **Started:** 2026-09-02
- **Owner run:** g0-clinic-foundation

## Summary

Retire starter-kit teams, put six clinic roles on `users`, disable public registration, and ship Golden Smile login plus navy Wave 1 chrome so staff can sign in and an Admin can create other staff.

## Scope in

- Drop `{current_team}` prefix, team switcher, invitations, team models/middleware/tests
- `ClinicRole` + `users.role`; policies/helpers; Admin-only staff create
- Fortify: no public register, no email verification as product; login/logout/remember/forgot/change-password; password min 10
- Session lifetime 30 minutes; timezone `Africa/Mogadishu`; brand Golden Smile Dental Clinic
- Split Golden Smile login; navy sidebar with eight Wave 1 items; header name + role
- Pest: auth + dashboard without teams; six-role login; 403 on staff-admin-only

## Scope out

- Domain tables (patients, appointments, billing, inventory, …) — G1
- Real dashboard KPIs — G7
- Full screenshot demo seed (six named staff, Maria Santos, …) — G9
- Live SMS/payment APIs, REST `/api/v1`, 2FA, i18n

## Verification (from architecture or user)

- [ ] `{current_team}` prefix, team switcher, invitations, and team models/middleware are gone; `route:list` shows `/dashboard` not `/{current_team}/dashboard`
- [ ] `users.role` is `admin|dentist|receptionist|nurse|accountant|lab`; guests hitting `/dashboard` redirect to `/login`
- [ ] Public registration disabled; login/logout/remember-me/forgot-password/change-password work; password min length 10
- [ ] Session lifetime 30 minutes
- [ ] Login is split Golden Smile layout; no sign-up CTA; footer lists six roles
- [ ] Chrome: navy sidebar (Wave 1 eight items), GS brand, header name + role
- [ ] Admin can create staff of any role; other roles cannot
- [ ] Each of the six roles can log in; Receptionist hitting staff-admin-only gets 403
- [ ] Team feature tests removed/replaced; auth + dashboard tests pass without teams
- [ ] `APP_NAME` / UI brand is Golden Smile Dental Clinic; app timezone `Africa/Mogadishu`

## Notes

- Architecture D1–D3, D9, §6, §7 login/chrome. Guests: `/login`, password reset, `/up` only — `/` should redirect to login (or dashboard if authenticated).
- Demo password `password12`. Do not add Composer/npm packages.
- Keep Fortify session, profile, password change. Do not enable 2FA.
- Placeholder module pages (patients … reports) are allowed so the eight nav items exist; no domain schema.
- Prior run residue: workflow Backlog B1 (Figure 7) expires 2026-10-02 — leave it.
