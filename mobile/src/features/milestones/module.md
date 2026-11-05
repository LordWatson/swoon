# Milestones & Countdowns ⏳ - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 5 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/Milestones/module.md`](../../../../api/app/Modules/Milestones/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: ⏳ Butter

## Screens, navigation, components
**Screens:** Milestones list and editor inside the Us tab; the 'days together' and next-milestone countdown cards appear on Home and at the top of Planner.

**Components:** `Card`, `Sheet`, countdown display, `Button`.

**Navigation:** Us tab -> Milestones; surfaced on Home and Planner.

**Offline:** counters compute locally from cached dates, so they are correct with no network at all.

**Local notifications:** via Reminders.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Butter**, emoji ⏳ (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/milestones/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
