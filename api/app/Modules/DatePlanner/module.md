# AI Date Planner 🍷

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 3 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/date-planner/module.md`.
> Signature: 🍷 Rose

## Purpose
The headline feature. Asks both partners the same questions independently, keeps each set of answers hidden until both have finished, then asks Claude for three date options that fairly compromise between them. The couple picks one and schedules it.

## User stories
- As a partner, I can start a date plan and answer a few quick questions.
- As a partner, I cannot see my partner's answers until I have given mine.
- As a couple, we get three options, each explaining why it works for both of us.
- As a couple, we can pick one and have it scheduled with reminders.
- As a couple, we can decide whether both of us must heart an option or either can just lock it in.

## Data model
**date_plan_requests** - id, couple_id, created_by_user_id, status (`collecting`|`generating`|`ready`|`chosen`|`expired`|`failed`), window_start, window_end, generated_at, chosen_option_id (nullable).

**date_plan_answers** - id, date_plan_request_id, user_id, when_pref (json), area, postcode_outcode (outcode ONLY - never a full postcode), duration_minutes, has_car (bool), budget_pence, vibe (json), romance_level (1-5), energy_level (1-5), notes (nullable), submitted_at. Unique on (request, user).

**date_plan_options** - id, date_plan_request_id, position (1-3), title, emoji, summary, timeline (json), estimated_cost_pence, travel_notes, why_both, ai_generation_id.

**date_plan_hearts** - date_plan_option_id, user_id, created_at.

**Private until both answer:** every row in `date_plan_answers` belonging to the other partner. The API does not return it - hiding it client-side would not be enough.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| POST | /api/date-plans | sanctum | Start a plan request | StartDatePlanRequest | DatePlanResource |
| GET | /api/date-plans/{id} | sanctum | State, own answers, options when ready | - | DatePlanResource |
| PUT | /api/date-plans/{id}/answers | sanctum | Submit or update own answers | SubmitAnswersRequest | DatePlanResource |
| POST | /api/date-plans/{id}/nudge | sanctum | Email the partner (24h cooldown) | - | 204 |
| POST | /api/date-plans/{id}/regenerate | sanctum | Ask for three more options | - | 202 |
| POST | /api/date-plan-options/{id}/heart | sanctum | Heart an option | - | DatePlanResource |
| DELETE | /api/date-plan-options/{id}/heart | sanctum | Un-heart | - | DatePlanResource |
| POST | /api/date-plan-options/{id}/schedule | sanctum | Lock it in and schedule | ScheduleDateRequest | PlannerEventResource |

## Business rules
1. **Answers are hidden until both partners have submitted.** Enforced in the API: a partner's answers are simply absent from the response until `submitted_at` is set on both. This is rule 5 of the UX rules and the heart of the feature.
2. Before the reveal the app shows '1 of 2 answered' - never a preview, never a partial summary.
3. Generation is **triggered by the second submission**, not by a button, and runs in a queued job.
4. **Exactly three options.** Not two, not five - three is the choice that does not become a chore.
5. Each option carries title, emoji, summary, timeline, estimated cost, travel notes, and a one-line 'why this works for both of you'. A response missing any field is invalid output.
6. **Lock-in mode is a couple setting** (`couples.lock_in_mode`): `both_heart` requires both hearts before scheduling; `either_locks` lets either partner schedule alone. Default `both_heart`.
7. The plan must **fairly compromise**, not average. Where the two answers conflict (one wants a big night out, one wants quiet), the options should include one that leans each way and one that genuinely bridges - and say so in 'why this works for both of you'.
8. **Dietary and accessibility needs from both profiles are always included** in the prompt and must be respected by every option. This is a correctness requirement, not a preference.
9. **Only the postcode outcode is stored and sent** (e.g. `NE1`, not `NE1 4ST`). Enough for the model to be useful, not enough to locate a home.
10. Budget is interpreted as a per-couple total unless the partner says otherwise, and the lower of the two budgets is the ceiling.
11. A request **expires** if the second partner never answers (14 days), and says so kindly.
12. Regeneration counts against the monthly cap and keeps the previous options in `ai_generations` (superseded), so nothing silently disappears.
13. Scheduling creates a **Planner event**, which is what then creates reminders. This module does not schedule notifications itself.
14. **No location tracking.** The area/outcode is a user-typed preference for this one plan, not a position, and is never shared with the partner as a location.

## AI usage (if any)
Prompt **`date_plan` v1** (see `docs/AI_PROMPTS.md`). Model key `config('ai.tasks.date_plan')` -> `strong`, effort `high`.

**Input (allow-listed):** both partners' answers, both partners' dietary and accessibility needs, first names, the couple's recent date history (titles and vibes only), optional bucket-list items. Nothing else.

**Output schema:** `{ options: [{ title, emoji, summary, timeline: [{ time, what }], estimated_cost_pence, travel_notes, why_both }] }` - exactly 3 options.

**Cost guard:** counts against `date_plan` cap (1/month Spark, 20/month Together). **Safety:** SafetyGuard runs on the free-text `notes` fields.

## Jobs, events & schedules
**Jobs:** `GenerateDatePlan` (dispatched on second submission; calls the AI module), `ExpireStaleDatePlans`.

**Events:** `DatePlanAnswersCompleted` (both in), `DatePlanReady`, `DatePlanChosen` (creates the Planner event).

**Scheduled:** `date-plans:expire` daily.

**Emails (Resend):** nudge ('Sam's waiting on your answers'), 'your three ideas are ready', 'date locked in'.

## Mobile
**Screens:** Dates tab (current request, past dates), Questionnaire (one question per screen, chips and sliders, progress bar), Waiting for partner (with nudge), Reveal (both answer sets side by side, partner colours), Options (three cards, AITag, heart, regenerate), Schedule sheet, Date detail.

**Components:** `Chip`, `Slider`, `ProgressBar`, `AvatarPair`, `AITag`, `Card`, `Sheet`, `Button`.

**Navigation:** Dates tab, and the quick-add sheet's 'Plan a date'. Three taps from Home to the first question.

**Offline:** answers are drafted locally and submitted when online; the questionnaire works fully offline. Generation needs the network.

**Local notifications:** none here - scheduling hands off to Reminders.

## Permissions & privacy
`DatePlanPolicy` - both partners may view the request; a partner may only write their **own** answers; neither may read the other's answers until both have submitted.

The reveal gate is a **server-side** rule, tested directly. The full postcode is never collected. Free-text notes go through SafetyGuard but are **not** shown to the partner before the reveal.

## Tests
`api/tests/Feature/DatePlanner/`.

The reveal rule gets its own tests and they are the most important in the module: the other partner's answers must be **absent from the JSON** before both have submitted, including on the detail endpoint, the index, and after a regenerate.

Also: generation fires exactly once on the second submission; three options always; dietary needs present in the prompt payload; outcode-only storage; `both_heart` vs `either_locks` behaviour; cap refusal is kind; nudge cooldown; expiry.

Run: `composer test -- --filter=DatePlanner`

## Known gaps / TODO
- No real venue data - options are model-generated suggestions, not bookable listings. Whether to add a venue source is an open product question.
- Estimated cost accuracy is unvalidated.
- Travel notes assume the driving answer but have no routing data behind them.
- 'Fair compromise' is a prompt instruction, not a measurable property. The eval fixtures should grow a conflict-case battery.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
