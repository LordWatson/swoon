# Date Debrief 💬 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/DateDebrief/module.md`](../../../../api/app/Modules/DateDebrief/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 💬 Sage

## Screens, navigation, components
**Screens:** Debrief prompt card on Home after a date, Debrief sheet (emoji scale + optional line), Waiting for partner, Reveal.

**Components:** `Sheet`, `Button`, `Card`, `AvatarPair`, `ProgressBar` ('1 of 2'), reveal animation.

**Navigation:** Home card, and from a past date in the Dates tab.

**Offline:** responses draft offline and queue.

**Local notifications:** one optional 'how was it?' the morning after.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Sage**, emoji 💬 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/date-debrief/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
