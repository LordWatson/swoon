# Insights (AI) 💡

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 7 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/insights/module.md`.
> Signature: 💡 Lavender

## Purpose
Light, actionable nudges to stay in tune - 'You've had three cosy nights in, fancy an adventure date?' Explicitly not therapy, not diagnosis, never judgemental.

## User stories
- As a couple, we get the occasional useful nudge about our patterns.
- As a couple, it suggests something we can actually do.
- As a couple, it never makes us feel judged.
- As a partner, I can dismiss one and never see it again.

## Data model
**insights** - id, couple_id, ai_generation_id, kind, body, suggested_action (json: label + deep link), shown_at, dismissed_at (nullable), acted_at (nullable), created_at.

Couple-shared. Inputs privacy-filtered.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/insights | sanctum | Current insights | - | InsightResource collection |
| POST | /api/insights/{id}/dismiss | sanctum | Dismiss permanently | - | 204 |
| POST | /api/insights/{id}/act | sanctum | Record that it was acted on | - | 204 |

## Business rules
1. **Not therapy, not diagnosis.** An insight observes a pattern and suggests one small, concrete thing. It never interprets a relationship, assesses its health, or implies a problem.
2. **Never judgemental and never comparative.** It must never say one partner does more, contributes less, or should try harder. This is the single most important rule in the module.
3. **At most one insight per couple per week**, and only when there is genuinely something to say. Silence is the default.
4. Every insight carries **one concrete suggested action** with a deep link - an insight with no action is just a comment.
5. **Dismissal is permanent** for that insight, and a dismissed kind is de-prioritised in future.
6. **Private content is excluded from the inputs** - in particular individual check-in answers, which would make this feel like surveillance.
7. **SafetyGuard runs on every generation.** If inputs suggest abuse, crisis or harm, no insight is produced at all - the signpost is shown instead. An upbeat suggestion in that context would be actively harmful.
8. AI-labelled, regenerable, ratable (UX rule 6).
9. Counts against the `insights` cap. Spark tier gets none.
10. Phrasing is a **question or an invitation**, never an instruction: 'fancy an adventure date?' not 'you should go on an adventure date'.

## AI usage (if any)
Prompt **`insights` v1**. Model key `config('ai.tasks.insights')` -> `strong`, effort `medium`.

**Input (allow-listed):** aggregate patterns only - counts and categories of recent dates, moment moods and tags over a window, challenge completion rate, upcoming planner categories, bucket-list items. **Deliberately aggregate, not raw content**, which both protects privacy and keeps the model from over-reading one bad day.

**Output schema:** `{ insights: [{ kind, body, suggested_action: { label, deep_link } }] }`, at most one.

**Cost guard:** `insights` cap. **Safety:** SafetyGuard, fail-closed - no insight rather than a risky one.

## Jobs, events & schedules
**Jobs:** `GenerateInsights`.

**Events:** `InsightGenerated`, `InsightDismissed` (feeds the de-prioritisation).

**Scheduled:** `insights:generate` weekly per couple, local time, after the recap.

**Emails:** none. An unsolicited email about relationship patterns would be a bad idea.

## Mobile
**Screens:** Insight card on Home (at most one), with a dismiss affordance and the action button.

**Components:** `Card`, `AITag`, `Button`, thumbs up/down.

**Navigation:** Home tab. The action deep-links into the relevant module (usually the date planner or roulette).

**Offline:** cached; dismissal queues.

**Local notifications:** none. An insight is something to find, not something to be interrupted by.

## Permissions & privacy
`InsightPolicy` - both partners may view, dismiss and act.

Inputs are **aggregates**, never raw private content. Individual check-in answers are excluded absolutely - using them would turn an opt-in private check-in into material for shared commentary, which would be a betrayal of the feature's promise.

## Tests
`api/tests/Feature/Insights/` - at most one per week; silence when there is nothing to say; **check-in answers and all private content absent from the payload**; **SafetyGuard suppresses the insight entirely** and shows the signpost; dismissal is permanent; every insight has an action; cap refusal is kind.

A **tone battery** asserts the output is never comparative or judgemental for a set of lopsided-activity fixtures - the failure mode most likely to hurt someone.

Run: `composer test -- --filter=Insights`

## Known gaps / TODO
- The tone battery needs designing properly; automated tone assertions are hard and partly a human-review job.
- Whether insights are wanted at all should be tested with real couples before investing further - this is the module most likely to feel intrusive.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
