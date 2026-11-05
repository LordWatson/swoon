# Check-in (Temperature Check) 🌡️ - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/CheckIn/module.md`](../../../../api/app/Modules/CheckIn/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🌡️ Sage

## Screens, navigation, components
**Screens:** Check-in prompt (opt-in, inside Us), three one-question screens, Own trend, Combined trend (only when both opted in), Preferences.

**Components:** `Slider`/`Chip`, `ProgressBar`, `Card`, `Button`, lock badge with 'Only you can see this' on every answer screen.

**Navigation:** Us tab only. **Deliberately not on Home** - a private check-in should not be prompted on a screen a partner may be looking over.

**Offline:** submits queue; own history is cached.

**Local notifications:** optional weekly reminder, to that user only, with a neutral body.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Sage**, emoji 🌡️ (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/check-in/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
