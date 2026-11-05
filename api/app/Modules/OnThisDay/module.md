# On This Day 🕰️

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 4 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/on-this-day/module.md`.
> Signature: 🕰️ Butter

## Purpose
Resurfaces moments from the same calendar day in previous years, and - while a couple is too new for that - from one month and six months ago. Shown as a playful card on Home, and optionally as a local notification.

## User stories
- As a couple, we see a moment from a year ago on the home screen.
- As a new couple, we still get something nice before our first anniversary.
- As a partner, I can opt in to a gentle daily notification.

## Data model
No tables of its own beyond **on_this_day_preferences** (user_id, notification_enabled, notify_at local time).

Reads `moments`, indexed on (couple_id, happened_on) so the same-day-different-year lookup stays cheap.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/on-this-day | sanctum | Today's resurfaced moments | - | OnThisDayResource |
| GET | /api/on-this-day/preferences | sanctum | Own notification preference | - | OnThisDayPreferencesResource |
| PUT | /api/on-this-day/preferences | sanctum | Update own preference | UpdateOnThisDayPreferencesRequest | OnThisDayPreferencesResource |

## Business rules
1. **Preference order:** same calendar day in a previous year (newest first), then '1 year ago' exactly, then 6 months, then 1 month. The first bucket with content wins.
2. **Never empty-handed without saying so warmly.** If there is nothing, the card is a friendly empty state with a prompt to add a moment - not a blank space.
3. **Private moments are excluded**, even from their own author's card, because the card is shown on a shared home screen that either partner may be looking at.
4. A moment is surfaced **at most once per day** and the module avoids repeating the same moment on consecutive days where alternatives exist.
5. **29 February** is handled explicitly: in a non-leap year, a 29 Feb moment surfaces on 28 Feb rather than being lost for three years.
6. Dates are evaluated in the **user's timezone**, so the card changes at their midnight.
7. The notification is **opt-in**, scheduled locally, and silent if there is nothing to show - it never fires just to say 'nothing today'.
8. Copy is playful, never maudlin: 'One year ago today 🕰️'.

## AI usage (if any)
None. This is a date query, and it should stay one - it costs nothing and never says anything odd.

## Jobs, events & schedules
**Jobs:** none required.

**Events:** none emitted.

**Scheduled:** nothing server-side - the card is computed on request and the notification is scheduled on-device. (If the opt-in email is added later, it gains a daily scheduled command.)

## Mobile
**Screens:** the On This Day card on Home; tapping it opens the Moment detail. A small 'see all from this day' list when there are several.

**Components:** `Card`, `EmptyState`, `AvatarPair`.

**Navigation:** Home tab, top of the screen.

**Offline:** served from the cached feed where possible, so the card is not blank on a cold, offline start.

**Local notifications:** one optional daily notification at the user's chosen time, scheduled on-device, skipped when there is nothing.

## Permissions & privacy
No policy of its own; it reads through `MomentPolicy` and **excludes private moments entirely**. Nothing is sent to the AI.

## Tests
`api/tests/Unit/OnThisDay/` - the bucket fallback ladder (year -> 6 months -> 1 month -> empty); **29 February handling**; timezone boundary; **private moments never surfaced, including to their author**; no repeat on consecutive days when alternatives exist.

Run: `composer test -- --filter=OnThisDay`

## Known gaps / TODO
- The 'avoid repeats' rule needs a concrete strategy (a seen-log vs deterministic rotation) - decide in P4.
- Whether an opt-in email version is wanted is undecided.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
