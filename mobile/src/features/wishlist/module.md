# Wishlist & Gift Vault 🎁 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/Wishlist/module.md`](../../../../api/app/Modules/Wishlist/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🎁 Apricot

## Screens, navigation, components
**Screens:** Wishlist (ours), Add wish, Gift Vault (mine, lock-badged throughout), Add gift idea, Gift reminders.

**Components:** `Card`, `Chip`, `Sheet`, `Button`, `EmptyState`, lock badge with 'Only you can see this'.

**Navigation:** Us tab -> Wishlist and Gift Vault, kept visually distinct so the two are never confused. The vault is never shown on a shared Home card.

**Offline:** both lists are readable offline; edits queue. The vault is stored in the normal cache - it is already scoped to one user's token.

**Local notifications:** gift reminders, scheduled to the author's device only, with a contentless body ('A gift reminder 🎁').

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Apricot**, emoji 🎁 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/wishlist/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
