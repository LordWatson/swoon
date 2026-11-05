# Love Notes 💕 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 6 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/LoveNotes/module.md`](../../../../api/app/Modules/LoveNotes/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 💕 Rose

## Screens, navigation, components
**Screens:** Love Notes list (Scheduled by me / For me), Write note (body + unlock date picker), Note detail (locked state shows only 'from Sam, unlocks on 14 Feb'), Unlocked note with an opening animation.

**Components:** `Card`, `Sheet`, `Button`, `EmptyState`, lock badge, date picker.

**Navigation:** Us tab -> Love Notes, and the quick-add sheet's 'Note'.

**Offline:** writing works offline and queues. A locked note holds no content locally, so there is nothing cached to leak.

**Local notifications:** scheduled from `/love-notes/schedule`, body contentless, re-synced like all local notifications.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Rose**, emoji 💕 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/love-notes/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
