# Accounts & Couple Linking 💞 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 1 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/Accounts/module.md`](../../../../api/app/Modules/Accounts/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 💞 Rose

## Screens, navigation, components
**Screens:** Welcome, Sign up, Sign in, Onboarding (one question per screen), Invite partner (share sheet + copyable code), Waiting for partner, Accept invite (from deep link), Us tab, Couple settings, Privacy & data, Unlink confirm, Delete account confirm.

**Navigation:** unauthenticated users see the Welcome stack; a user without a couple sees the Invite/Waiting stack; a coupled user sees the five tabs. The deep link `swoon://invite/<code>` and the universal link `https://link.swoon.test/invite/<code>` both resolve to Accept invite, surviving a cold start and an app-install detour.

**Local state:** the token lives in `expo-secure-store`, never AsyncStorage. `useMe()` is the single source of auth state.

**Offline:** sign-in needs the network. Once signed in, the app opens to cached content and a quiet 'offline' chip.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Rose**, emoji 💞 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/accounts/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
