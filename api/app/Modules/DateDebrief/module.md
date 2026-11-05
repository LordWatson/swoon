# Date Debrief 💬

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/date-debrief/module.md`.
> Signature: 💬 Sage

## Purpose
After a date, each partner rates it on an emoji scale and adds a line. It feeds future AI suggestions and creates a Moment, so a date leaves something behind.

## User stories
- As a partner, I can rate a date in two taps.
- As a couple, our debriefs make the next suggestion better.
- As a couple, a debriefed date becomes a moment.
- As a partner, I cannot see my partner's rating until I have given mine.

## Data model
**date_debriefs** - id, couple_id, planner_event_id, revealed_at (nullable), moment_id (nullable), created_at.

**date_debrief_responses** - id, date_debrief_id, user_id, rating (1-5 emoji scale), note (nullable), would_repeat (bool), submitted_at. Unique on (debrief, user).

**Private until both respond:** the other partner's response. Withheld by the API (UX rule 5).

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/date-debriefs/pending | sanctum | Dates awaiting a debrief | - | DateDebriefResource collection |
| GET | /api/date-debriefs/{id} | sanctum | One debrief, respecting the reveal | - | DateDebriefResource |
| PUT | /api/date-debriefs/{id}/response | sanctum | Submit own response | SubmitDebriefRequest | DateDebriefResource |
| POST | /api/date-debriefs/{id}/nudge | sanctum | Nudge the partner (24h cooldown) | - | 204 |

## Business rules
1. A debrief is offered **after** a date event has passed - once, gently, and it expires quietly after 7 days if ignored.
2. **Two taps minimum:** pick an emoji, done. The note and `would_repeat` are optional.
3. **Neither response is visible until both have submitted**, enforced in the API.
4. Once both have responded it reveals, and **offers** to create a Moment from the date - never silently.
5. A skipped debrief has **no consequence** and is never mentioned again.
6. Debrief data feeds `date_plan` and `insights` as **aggregate preference signal** (what kinds of dates land well), not as quoted text.
7. A partner's **note is never shown to the other partner as AI output**, and never quoted in a recap.
8. Ratings are never compared between partners, and never shown as 'you liked it more than they did'.

## AI usage (if any)
No prompt of its own. Aggregated ratings and `would_repeat` are an allow-listed **input** to `date_plan` and `insights`. Free-text notes are excluded from shared AI output.

## Jobs, events & schedules
**Jobs:** `OfferDateDebrief` (after an event passes), `ExpireStaleDebriefs`.

**Events:** `DateDebriefCompleted` (both in; feeds the recap and may create a Moment).

**Scheduled:** `debriefs:offer` hourly; `debriefs:expire` daily.

**Emails (Resend):** nudge only.

## Mobile
**Screens:** Debrief prompt card on Home after a date, Debrief sheet (emoji scale + optional line), Waiting for partner, Reveal.

**Components:** `Sheet`, `Button`, `Card`, `AvatarPair`, `ProgressBar` ('1 of 2'), reveal animation.

**Navigation:** Home card, and from a past date in the Dates tab.

**Offline:** responses draft offline and queue.

**Local notifications:** one optional 'how was it?' the morning after.

## Permissions & privacy
`DateDebriefPolicy` - both may view the debrief; a partner may write only their own response; neither reads the other's until both have submitted.

Notes stay out of shared AI output. Ratings are never presented comparatively.

## Tests
`api/tests/Feature/DateDebrief/` - **the other response is absent from the JSON until both have submitted**; two-tap minimum (note optional); Moment creation offered not automatic; skipping has no consequence and no repeat prompt; notes absent from the recap/insights payloads; nudge cooldown; expiry.

Run: `composer test -- --filter=DateDebrief`

## Known gaps / TODO
- Whether a 1-5 scale is too granular for an emoji picker; 3 may be kinder and quicker.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
