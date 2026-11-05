# Date Roulette 🎡 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 6 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/DateRoulette/module.md`](../../../../api/app/Modules/DateRoulette/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🎡 Rose

## Screens, navigation, components
**Screens:** Date Roulette (filter chips + spin), Result card, from the Dates tab and the quick-add sheet's 'Date idea'.

**Components:** `Chip`, `Button`, `Card`, `Sheet`, the wheel animation (respecting reduce-motion - it becomes a quick cross-fade).

**Navigation:** Dates tab, and quick-add.

**Offline:** the idea library is cached on the device, so the wheel spins with no network at all - which is often exactly when a couple is standing around deciding.

**Local notifications:** none.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Rose**, emoji 🎡 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/date-roulette/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
