# Shared Planner 🗓️ - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 5 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/Planner/module.md`](../../../../api/app/Modules/Planner/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🗓️ Apricot

## Screens, navigation, components
**Screens:** Planner tab (Upcoming / Past), Event detail, Add/Edit event sheet.

**Components:** `Card`, `Chip` (category), `Sheet`, `Button`, `EmptyState`, countdown display.

**Navigation:** Planner tab and the quick-add sheet's 'Plan'.

**Offline:** full create/edit offline with optimistic insert and Saving/Saved, same queue as Moments.

**Local notifications:** scheduled by the Reminders module from this module's events.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Apricot**, emoji 🗓️ (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/planner/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
