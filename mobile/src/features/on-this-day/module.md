# On This Day 🕰️ - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 4 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/OnThisDay/module.md`](../../../../api/app/Modules/OnThisDay/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🕰️ Butter

## Screens, navigation, components
**Screens:** the On This Day card on Home; tapping it opens the Moment detail. A small 'see all from this day' list when there are several.

**Components:** `Card`, `EmptyState`, `AvatarPair`.

**Navigation:** Home tab, top of the screen.

**Offline:** served from the cached feed where possible, so the card is not blank on a cold, offline start.

**Local notifications:** one optional daily notification at the user's chosen time, scheduled on-device, skipped when there is nothing.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Butter**, emoji 🕰️ (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/on-this-day/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
