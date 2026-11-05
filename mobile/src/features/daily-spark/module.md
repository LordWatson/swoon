# Daily Spark 🔥 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 6 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/DailySpark/module.md`](../../../../api/app/Modules/DailySpark/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🔥 Butter

## Screens, navigation, components
**Screens:** Daily Spark card on Home, Answer sheet, Reveal, History.

**Components:** `Card`, `Sheet`, `Button`, `AvatarPair`, `ProgressBar` ('1 of 2'), reveal animation, `EmptyState`.

**Navigation:** Home tab, prominent. Two taps to answer.

**Offline:** today's question is cached so it is readable offline; an answer drafted offline is queued and sent on reconnect.

**Local notifications:** optional single daily reminder at a user-chosen time, and one when the partner's answer unlocks the reveal.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Butter**, emoji 🔥 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/daily-spark/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
