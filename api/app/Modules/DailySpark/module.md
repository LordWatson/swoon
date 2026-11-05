# Daily Spark 🔥

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 6 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/daily-spark/module.md`.
> Signature: 🔥 Butter

## Purpose
One question a day - 'What's a smell that reminds you of us?' Both partners answer; answers unlock only once both have replied. Intended to be the strongest daily-retention driver in the app, which is exactly why it must never feel like homework.

## User stories
- As a couple, we get one question a day.
- As a partner, I cannot see my partner's answer until I have given mine.
- As a couple, we can look back at past questions and answers.
- As a partner, skipping a day costs me nothing.

## Data model
**spark_questions** - id, body, theme, is_seasonal, active. A curated library, seeded; not generated per couple.

**spark_prompts** - id, couple_id, spark_question_id, for_date, revealed_at (nullable). Unique on (couple_id, for_date).

**spark_answers** - id, spark_prompt_id, user_id, body, answered_at. Unique on (prompt, user).

**Private until both answer:** the other partner's `spark_answers` row. Withheld by the API, not just the UI.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/spark/today | sanctum | Today's question and reveal state | - | SparkPromptResource |
| POST | /api/spark/today/answer | sanctum | Answer today's question | AnswerSparkRequest | SparkPromptResource |
| GET | /api/spark/history | sanctum | Past prompts and revealed answers | - | SparkPromptResource collection |
| POST | /api/spark/{id}/nudge | sanctum | Nudge the partner (24h cooldown) | - | 204 |

## Business rules
1. **One question per couple per day**, the same question for both partners, assigned in the couple's local day.
2. **Neither answer is visible until both have answered.** The API withholds the other partner's answer; before that the app shows '1 of 2 answered'.
3. Once both have answered, the prompt is **revealed** with a small celebration, and stays revealed forever in history.
4. **An unanswered day is not a failure.** It simply rolls past. No red badge, no 'you missed it', no streak break - streaks pause (UX rule 8).
5. A question is **not repeated** for a couple until the library is exhausted, then it reshuffles.
6. Answering is a single screen with a text field and a send button - two taps from Home.
7. Answers are **not editable after reveal** (it would change what the partner already read), but are editable before.
8. Questions are drawn from a **curated library**, not generated per couple - it costs nothing, it is reviewable for tone, and it cannot produce something tactless.
9. Seasonal questions are eligible only in their window.

## AI usage (if any)
**None in the normal path** - questions come from the curated library. Prompt `spark_question` v1 (model key `light`) exists only as a tool for **authoring new library questions offline**, reviewed by a human before being seeded. The model never speaks directly to a couple here.

## Jobs, events & schedules
**Jobs:** `AssignDailySpark` (per couple, idempotent on (couple_id, for_date)).

**Events:** `SparkAnswered`, `SparkRevealed` (feeds the gentle streak and the Weekly Recap); listens for `CoupleLinked` to assign the first question immediately.

**Scheduled:** `spark:assign` hourly - assigns the day's question to couples whose local midnight has passed.

**Emails (Resend):** nudge only. No daily email - that would be nagging.

## Mobile
**Screens:** Daily Spark card on Home, Answer sheet, Reveal, History.

**Components:** `Card`, `Sheet`, `Button`, `AvatarPair`, `ProgressBar` ('1 of 2'), reveal animation, `EmptyState`.

**Navigation:** Home tab, prominent. Two taps to answer.

**Offline:** today's question is cached so it is readable offline; an answer drafted offline is queued and sent on reconnect.

**Local notifications:** optional single daily reminder at a user-chosen time, and one when the partner's answer unlocks the reveal.

## Permissions & privacy
`SparkPromptPolicy` / `SparkAnswerPolicy` - a partner may write only their own answer and may not read the other's until both have answered.

Answers are **not** sent to the AI for the recap unless revealed, and never quoted as the partner's words in AI output.

## Tests
`api/tests/Feature/DailySpark/` - **the other answer is absent from the JSON until both have answered**, on today and history endpoints; assignment is idempotent per couple per local day; no repeat until the library is exhausted; a missed day creates no penalty; edit allowed before and refused after reveal; nudge cooldown.

Run: `composer test -- --filter=DailySpark`

## Known gaps / TODO
- The question library needs writing - target ~200 questions for launch, reviewed for tone and for assumptions about living together, children, finances.
- Questions must not assume a gender, a living arrangement or a life stage. Needs an explicit review pass.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
