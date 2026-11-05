# Check-in (Temperature Check) 🌡️

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/check-in/module.md`.
> Signature: 🌡️ Sage

## Purpose
An optional weekly three-tap check-in on connection, stress and quality time. **Individual answers are private forever.** Only a combined trend is shared, and only if both partners opt in. The most privacy-sensitive feature in the product.

## User stories
- As a partner, I can check in on how things feel, privately.
- As a partner, my individual answers are never shown to my partner.
- As a couple, if we both opt in, we can see a combined trend.
- As a partner, I can opt out at any time and the shared trend stops.

## Data model
**check_in_responses** - id, couple_id, user_id, week_starting, connection (1-5), stress (1-5), quality_time (1-5), submitted_at. Unique on (user, week_starting).

**check_in_preferences** - user_id, enabled, share_trend (bool, default **false**), reminder_day, reminder_time.

**Private to one partner: every single response row, permanently.** There is no endpoint that returns another user's response. The only shared artefact is an aggregated trend, computed server-side, and only when both `share_trend` flags are true.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/check-in/current | sanctum | This week's own check-in state | - | CheckInResource |
| PUT | /api/check-in/current | sanctum | Submit own check-in | SubmitCheckInRequest | CheckInResource |
| GET | /api/check-in/mine | sanctum | Own history and trend | - | CheckInTrendResource |
| GET | /api/check-in/trend | sanctum | Combined trend (only if both opted in) | - | CheckInTrendResource |
| PUT | /api/check-in/preferences | sanctum | Update own preferences | UpdateCheckInPreferencesRequest | CheckInPreferencesResource |

## Business rules
1. **Entirely optional**, off by default. Nothing in the app pressures a user to enable it.
2. **An individual answer is never visible to the partner. There is no exception, no flag, and no endpoint that could return one.** Not even 'they checked in' with a value.
3. **The combined trend requires both partners' `share_trend` to be true.** If either turns it off, the shared trend disappears immediately for both.
4. The combined trend is **aggregated and coarse** - a direction over several weeks, not a weekly pair of numbers from which one partner's answer could be derived.
5. **The trend is suppressed when too few weeks exist** to prevent arithmetic de-anonymisation: with one week of data and one's own answer known, the other's is trivially recoverable. Minimum 4 weeks of both partners' data before any trend is shown.
6. Three taps, three questions, one screen each. No free text - free text would be impossible to aggregate privately.
7. **This is not a diagnosis and must never be framed as one.** No scores, no labels, no 'your relationship is...'. A trend line and nothing more.
8. **Individual answers are never sent to any AI prompt.** Not to insights, not to the recap. Using them would turn a private check-in into shared commentary.
9. A missed week is simply missing - no streak, no prompt, no guilt.
10. Opting out deletes nothing of the user's own history, but removes it from any shared view at once.
11. If responses suggest crisis, the user alone sees a kind signpost. **The partner is not told.** Telling them could be dangerous.

## AI usage (if any)
**Nothing from this module reaches a shared AI prompt. Ever.**

The only AI touch is a private, user-only SafetyGuard pass on the user's own submission, which may show that user a signpost. Its result is never shared with the partner, never stored as content, and never logged with the response.

## Jobs, events & schedules
**Jobs:** `ComputeCheckInTrend` (aggregate only, both-opted-in only).

**Events:** `CheckInSubmitted` (no payload beyond ids - the values do not travel).

**Scheduled:** `check-in:offer` weekly per user, local time.

**Emails:** none. An email about a private check-in could be read by someone else.

## Mobile
**Screens:** Check-in prompt (opt-in, inside Us), three one-question screens, Own trend, Combined trend (only when both opted in), Preferences.

**Components:** `Slider`/`Chip`, `ProgressBar`, `Card`, `Button`, lock badge with 'Only you can see this' on every answer screen.

**Navigation:** Us tab only. **Deliberately not on Home** - a private check-in should not be prompted on a screen a partner may be looking over.

**Offline:** submits queue; own history is cached.

**Local notifications:** optional weekly reminder, to that user only, with a neutral body.

## Permissions & privacy
`CheckInResponsePolicy` - **owner only, for every action. No partner access exists at any visibility setting.**

`CheckInTrendPolicy` - the combined trend is readable only when both partners have `share_trend = true` **and** the minimum window is met.

This module is the clearest test of whether the product's privacy promises are real. The tests treat any partner-visible individual value as a critical failure.

## Tests
`api/tests/Feature/CheckIn/` - **the partner receives 403/404 on every route that could expose an individual response** (one test per route); **no individual value appears in any response body the partner can fetch**, including the trend; the trend requires both opt-ins and vanishes the moment either opts out; **the trend is suppressed below the 4-week minimum** (the de-anonymisation guard); **check-in values are absent from every AI payload** (insights and recap, asserted directly); a crisis signpost is shown to the submitter and **not** to the partner.

Run: `composer test -- --filter=CheckIn`

## Known gaps / TODO
- The 4-week minimum is a reasoned guess at the de-anonymisation threshold; it deserves a proper look before launch.
- Whether this feature should exist at all is a fair question - it is the one most likely to be misused as a scorecard. Build it last, and test it with real couples before promoting it.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
