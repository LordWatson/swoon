# Weekly Recap (AI) ✨

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 7 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/recap/module.md`.
> Signature: ✨ Lavender

## Purpose
Every week, Claude summarises what the couple did - dates, moments, challenges, what is coming up - in a warm, short, genuinely fun read. Shown as an in-app card, with an optional email.

## User stories
- As a couple, we get a warm summary of our week.
- As a couple, it is short enough that we actually read it.
- As a partner, I can get it by email.
- As a couple, we can rate it so it gets better.

## Data model
**recaps** - id, couple_id, week_starting, ai_generation_id, body (json: headline, paragraphs, highlights, looking_ahead), email_sent_at (nullable), read_at (nullable). Unique on (couple_id, week_starting).

**recap_preferences** - user_id, email_enabled, in_app_enabled.

Content is couple-shared. Inputs are privacy-filtered before generation.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/recaps/latest | sanctum | This week's recap | - | RecapResource |
| GET | /api/recaps | sanctum | Past recaps | - | RecapResource collection |
| GET | /api/recaps/{id} | sanctum | One recap | - | RecapResource |
| POST | /api/recaps/{id}/regenerate | sanctum | Regenerate (counts to cap) | - | 202 |
| PUT | /api/recap-preferences | sanctum | Update own preferences | UpdateRecapPreferencesRequest | RecapPreferencesResource |

## Business rules
1. **One recap per couple per week**, generated after the week closes in the couple's timezone. Idempotent on (couple_id, week_starting).
2. **A quiet week still gets a kind recap** - never 'you did nothing this week'. If there is genuinely little, the recap is shorter and looks forward instead.
3. **Short.** A headline, two or three short paragraphs, a few highlights, and one line about what is coming. If it is long, it will not be read.
4. Generated in a **queued job**, never in a request.
5. **AI-labelled** in the app (Lavender ✨), regenerable, and ratable.
6. **Private content is excluded from the inputs**: private moments, gift vault, check-in answers, unsent love notes, un-revealed spark answers. Allow-listed per the AI module.
7. Never quotes a partner's words as if the AI wrote them, and never attributes AI text to a partner.
8. **SafetyGuard runs on the generation.** If the week's content suggests crisis or abuse, the recap is replaced by the signpost and nothing else - a cheerful summary would be grotesque in that situation.
9. Counts against the `weekly_recap` cap; Spark tier gets none, and is told so kindly.
10. The email is opt-in per user, sent once (`email_sent_at` makes it idempotent), and contains the recap itself.
11. A recap is **never regenerated silently** - the old one is kept and superseded.

## AI usage (if any)
Prompt **`weekly_recap` v1**. Model key `config('ai.tasks.weekly_recap')` -> `strong`, effort `medium`.

**Input (allow-listed):** first names; this week's non-private moments (body, mood, tags, date); dates that happened; challenges completed; revealed spark answers; next week's planner events (titles and dates). Nothing private, ever.

**Output schema:** `{ headline, paragraphs: [string], highlights: [{ emoji, text }], looking_ahead }`.

**Cost guard:** `weekly_recap` cap. **Safety:** SafetyGuard on output and on the free-text inputs.

## Jobs, events & schedules
**Jobs:** `GenerateWeeklyRecap`, `SendRecapEmail`.

**Events:** `RecapGenerated`.

**Scheduled:** `recaps:generate` hourly - picks up couples whose local week has just closed, so everyone gets theirs on their own Monday.

**Emails (Resend):** the weekly recap, opt-in.

## Mobile
**Screens:** Recap card on Home when fresh, Recap detail, Past recaps list.

**Components:** `Card`, `AITag`, `Button` (regenerate), thumbs up/down, `EmptyState`, `AvatarPair`.

**Navigation:** Home tab card; history from the Us tab.

**Offline:** the latest recap is cached and reads fine offline.

**Local notifications:** optional 'your week in review is ready ✨'.

## Permissions & privacy
`RecapPolicy` - both partners may view; preferences are per user.

The input allow-list is the whole privacy story here, and it is tested field by field in the AI module. A recap must never contain anything either partner marked private.

## Tests
`api/tests/Feature/Recap/` - idempotent per couple per week; **a quiet week produces a kind recap, not a scolding one**; **private content absent from the generated payload** (one assertion per private source); SafetyGuard replacement path; cap refusal is kind; email sent once; regenerate supersedes rather than overwrites; fake driver used.

Run: `composer test -- --filter=Recap`

## Known gaps / TODO
- Tone needs real review once there is real content - the risk is twee.
- No eval yet for 'is this recap actually nice to read'. Human review of a sample is the only honest method at first.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
