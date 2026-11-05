# AI Date Planner 🍷 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 3 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/DatePlanner/module.md`](../../../../api/app/Modules/DatePlanner/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🍷 Rose

## Screens, navigation, components
**Screens:** Dates tab (current request, past dates), Questionnaire (one question per screen, chips and sliders, progress bar), Waiting for partner (with nudge), Reveal (both answer sets side by side, partner colours), Options (three cards, AITag, heart, regenerate), Schedule sheet, Date detail.

**Components:** `Chip`, `Slider`, `ProgressBar`, `AvatarPair`, `AITag`, `Card`, `Sheet`, `Button`.

**Navigation:** Dates tab, and the quick-add sheet's 'Plan a date'. Three taps from Home to the first question.

**Offline:** answers are drafted locally and submitted when online; the questionnaire works fully offline. Generation needs the network.

**Local notifications:** none here - scheduling hands off to Reminders.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Rose**, emoji 🍷 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/date-planner/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
