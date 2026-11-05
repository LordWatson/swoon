# Year in Moments 📖 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 11 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/YearInMoments/module.md`](../../../../api/app/Modules/YearInMoments/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 📖 Lavender

## Screens, navigation, components
**Screens:** Year in Moments story (a scrollable chapter view), Curation (include/exclude), Book options, Proof preview, Order, Order status.

**Components:** `Card`, `AITag`, `Button`, `Sheet`, `EmptyState`, image gallery.

**Navigation:** Us tab -> Year in Moments, promoted on Home in December.

**Offline:** the story is cached and readable offline; ordering needs the network.

**Local notifications:** one 'your year is ready 📖'.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Lavender**, emoji 📖 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/year-in-moments/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
