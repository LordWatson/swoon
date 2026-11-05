# Billing & Entitlements 🧾 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only.
**Owner phase:** Phase 8 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/Billing/module.md`](../../../../api/app/Modules/Billing/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 🧾 Sage

## Screens, navigation, components
**Screens:** Paywall (on-brand, honest), Manage subscription, Trial banner, Cap-reached sheets (per feature), Add-on store (physical, Stripe), Order history.

**Components:** `Card`, `Button`, `Sheet`, `Chip`, `EmptyState`.

**RevenueCat SDK** handles purchase; the app then refetches `/api/entitlements` and trusts only that. It never grants access from the SDK's local result.

**Navigation:** Us tab -> Subscription; cap sheets appear in context.

**Offline:** the last known entitlement is cached so the app is usable offline, and it fails **closed** for new paid actions rather than open.

**Local notifications:** none. Billing nudges belong in email.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Sage**, emoji 🧾 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/billing/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - Created during project setup.
