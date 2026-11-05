# Romantic Challenges 💌

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 6 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/challenges/module.md`.
> Signature: 💌 Rose

## Purpose
Small, light challenges - 'Leave a note somewhere they'll find it today 💌'. A weekly set, completable by one or both partners, with gentle streaks that pause rather than break.

## User stories
- As a couple, we get a small set of challenges each week.
- As a partner, I can mark one done.
- As a couple, we build a gentle streak that never guilt-trips us.
- As a couple, completing a challenge can become a moment.

## Data model
**challenges** - id, title, body, effort (`tiny`|`small`|`medium`), category, requires_both (bool), is_seasonal, active. Curated library.

**couple_challenges** - id, couple_id, challenge_id, week_starting, position. Unique on (couple_id, challenge_id, week_starting).

**challenge_completions** - id, couple_challenge_id, user_id, completed_at, moment_id (nullable - if it became a moment).

**streaks** - couple_id, kind, current_length, longest_length, last_active_week, paused_at (nullable).

Nothing partner-private; completion is shared and is the point.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/challenges/current | sanctum | This week's set + completion state | - | CoupleChallengeResource collection |
| POST | /api/challenges/{id}/complete | sanctum | Mark done | CompleteChallengeRequest | CoupleChallengeResource |
| DELETE | /api/challenges/{id}/complete | sanctum | Undo a completion | - | CoupleChallengeResource |
| GET | /api/challenges/history | sanctum | Past weeks | - | CoupleChallengeResource collection |
| GET | /api/streaks | sanctum | Current gentle streaks | - | StreakResource |

## Business rules
1. **Three challenges per week**, assigned at the couple's local start of week, mixing efforts so there is always a `tiny` one.
2. A challenge marked `requires_both` completes only when both partners mark it; otherwise one partner is enough and it shows who did it.
3. **Streaks pause, they never break.** A quiet week sets `paused_at` and the copy is kind: 'Your streak's having a lie-in 😴'. `current_length` is preserved, not zeroed.
4. There is **no penalty, no red badge and no shame copy** for an incomplete week (UX rule 8). Incomplete challenges simply roll away.
5. Completing a challenge **offers** to create a Moment - it never creates one silently.
6. Completion is undoable, because a mis-tap should not falsify the archive.
7. Challenges come from a **curated library**, reviewed for tone - never generated live for a couple.
8. A challenge is not repeated until the library is exhausted.
9. No challenge may require spending money, assume cohabitation, assume children, or assume physical ability. The library needs an explicit review pass for this.
10. Seasonal challenges are eligible only in their window.

## AI usage (if any)
**None in the normal path** - the weekly set comes from the curated library. Premium date packs (Phase 11) use prompt `date_pack` v1 to generate themed bundles, which are **reviewed before sale**, not served live.

## Jobs, events & schedules
**Jobs:** `AssignWeeklyChallenges` (idempotent per couple per week), `UpdateStreak`.

**Events:** `ChallengeCompleted` (feeds streaks and the Weekly Recap), `StreakPaused`.

**Scheduled:** `challenges:assign` hourly, handling couples by local week start.

**Emails (Resend):** none weekly by default - the Weekly Recap covers it. An opt-in 'new challenges' email is possible later.

## Mobile
**Screens:** Challenge card on Home (this week's set), Challenges list, Challenge detail with complete action, Streak card, History.

**Components:** `Card`, `Chip`, `Button`, `AvatarPair`, `EmptyState`, confetti on completion.

**Navigation:** Home tab card; full list from the card.

**Offline:** completions are queued optimistically and synced.

**Local notifications:** optional weekly 'new challenges' nudge, and one when the partner completes a `requires_both` challenge.

## Permissions & privacy
`CoupleChallengePolicy` - both partners may view and complete. Nothing private. Only counts and titles reach the Weekly Recap prompt.

## Tests
`api/tests/Feature/Challenges/` - weekly assignment idempotent per local week; `requires_both` logic; **streak pauses without resetting `current_length`**; no penalty state exists for an incomplete week; completion undo; optional moment creation is never silent; no repeat until library exhausted.

Run: `composer test -- --filter=Challenges`

## Known gaps / TODO
- Challenge library needs writing - target ~150 for launch, with the inclusivity review above.
- Whether streaks should be visible at all is worth testing; they are the part most likely to feel like pressure.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
