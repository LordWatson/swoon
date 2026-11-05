# Billing & Entitlements 🧾

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 8 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/billing/module.md`.
> Signature: 🧾 Sage

## Purpose
Decides, server-side, what a couple is entitled to. Takes RevenueCat webhooks for subscriptions and digital purchases, Stripe for physical goods and real-world services, and exposes one honest answer the app renders without arguing.

## User stories
- As a partner, I can subscribe and we both get access.
- As a couple, our 7-day trial starts when the second of us joins.
- As a couple, hitting a limit is explained kindly, not as an error.
- As the business, access is never decided by the client.

## Data model
**couple_entitlements** - id, couple_id, tier (`spark`|`together`|`forever`), source (`revenuecat`|`stripe`|`trial`|`manual`), payer_user_id (nullable), entitlement_id (RevenueCat identifier), started_at, expires_at (nullable), will_renew (bool), raw (json), updated_at. One active row per couple.

**billing_events** - id, couple_id (nullable), provider, provider_event_id (unique - makes webhook handling idempotent), type, payload (json), processed_at, error (nullable).

**trials** - couple_id, user_ids (json - so a user cannot farm trials by re-linking), started_at, ends_at.

**orders** (Stripe, physical) - id, couple_id, user_id, addon_key, amount_pence, status, stripe_payment_intent_id, shipping (json, encrypted), fulfilled_at.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/entitlements | sanctum | What this couple may use | - | EntitlementResource |
| POST | /api/webhooks/revenuecat | signature | RevenueCat events | - | 204 |
| POST | /api/webhooks/stripe | signature | Stripe events (physical only) | - | 204 |
| POST | /api/orders | sanctum | Start a Stripe order for a physical add-on | StoreOrderRequest | OrderResource |
| GET | /api/orders | sanctum | Own orders | - | OrderResource collection |

## Business rules
1. **Entitlements are decided server-side, always.** The app renders what `GET /api/entitlements` says. A modified client gains nothing.
2. **One subscription per couple.** Either partner may pay; both get access.
3. **Digital goods and subscriptions go through Apple/Google IAP via RevenueCat. Only physical goods and real-world services use Stripe.** Never the other way round; never an in-app link out to buy a digital item. See `docs/PRICING.md` section 5.
4. **Webhooks are idempotent** on `provider_event_id`, and are verified by signature before anything is read from the body.
5. Webhooks are processed in a **queued job**; the endpoint acknowledges fast so the provider does not retry a slow handler.
6. **Webhook order is not guaranteed.** State is reconciled from the event's own timestamps, never by assuming the latest-received event is the latest-occurring.
7. **The trial starts when the second partner joins** (on `CoupleLinked`), lasts 7 days, and is one per couple **and** one per user - recorded in `trials.user_ids`.
8. **Entitlement follows the payer on unlink.** The payer keeps the tier; the other partner drops to Spark at the end of the current period and is told plainly **before** confirming the unlink. No one's data is deleted for a billing reason.
9. **A downgrade never destroys content.** Over-cap moments become read-only, not deleted. This is non-negotiable.
10. Hitting a cap or a gate is a **kind, specific message** with the reset date (UX rule 8), never an error or a hard sell.
11. **Prices live in `config/pricing.php`**, and the store's price is authoritative if they ever disagree.
12. Paywalls are honest: what you get, what it costs, how to cancel. No countdown pressure, no dark patterns.
13. Shipping addresses for physical orders are encrypted at rest and are never sent to the AI.

## AI usage (if any)
None. Billing never involves the model.

## Jobs, events & schedules
**Jobs:** `ProcessRevenueCatWebhook`, `ProcessStripeWebhook`, `ReconcileEntitlements` (safety net for missed webhooks), `ExpireTrials`.

**Events:** `EntitlementChanged` (re-evaluates caps), `TrialStarted`, `TrialEnded`, `OrderPaid`; listens for `CoupleLinked` (start trial) and `CoupleUnlinked` (reassign entitlement to payer).

**Scheduled:** `billing:reconcile` daily; `trials:expire` hourly.

**Emails (Resend):** trial started, trial ending tomorrow (once, honest), payment problem, order confirmation, order shipped.

## Mobile
**Screens:** Paywall (on-brand, honest), Manage subscription, Trial banner, Cap-reached sheets (per feature), Add-on store (physical, Stripe), Order history.

**Components:** `Card`, `Button`, `Sheet`, `Chip`, `EmptyState`.

**RevenueCat SDK** handles purchase; the app then refetches `/api/entitlements` and trusts only that. It never grants access from the SDK's local result.

**Navigation:** Us tab -> Subscription; cap sheets appear in context.

**Offline:** the last known entitlement is cached so the app is usable offline, and it fails **closed** for new paid actions rather than open.

**Local notifications:** none. Billing nudges belong in email.

## Permissions & privacy
`EntitlementPolicy` - both partners may read the couple's entitlement. `OrderPolicy` - the purchasing user only, since an order may be a surprise gift.

Payment details never touch our servers - both providers are hosted checkout. Shipping addresses are encrypted. Nothing here is sent to AI.

## Tests
`api/tests/Feature/Billing/` - **entitlement is never taken from the client**; webhook signature verification rejects a forged body; **webhook idempotency on replay**; out-of-order webhooks reconcile correctly; trial starts on second join and cannot be farmed by re-linking; **unlink moves the entitlement to the payer and the other partner keeps their data**; **a downgrade makes over-cap content read-only, never deleted**; a digital add-on cannot be purchased through the Stripe path (guarded and tested).

Run: `composer test -- --filter=Billing`

## Known gaps / TODO
- RevenueCat project, Apple and Google developer accounts, and Stripe account are not yet set up (founder action).
- Store rules must be re-checked immediately before launch - ADR-011.
- Refund handling on the Stripe side is unspecified.
- Concierge add-ons need a fulfilment workflow before they can be sold - Phase 11.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
