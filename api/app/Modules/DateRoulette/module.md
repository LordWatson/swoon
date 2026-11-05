# Date Roulette 🎡

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 6 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/date-roulette/module.md`.
> Signature: 🎡 Rose

## Purpose
The 'we can't decide' button. Spins a wheel of quick date ideas filtered by budget, energy and time available. Deliberately zero-AI-cost, so it is free for everyone and always instant.

## User stories
- As a couple, we can tap one button and get a date idea right now.
- As a couple, we can filter by how much time, money and energy we have.
- As a couple, we can spin again, or turn an idea into a planned date.

## Data model
**date_ideas** - id, title, emoji, summary, effort, budget_band (`free`|`cheap`|`mid`|`splash`), energy (1-5), duration_minutes, needs_car (bool), indoor_outdoor, is_seasonal, active. Curated library.

**date_roulette_spins** - id, couple_id, user_id, date_idea_id, filters (json), created_at. Kept so the wheel can avoid immediate repeats and so we learn which ideas land.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| POST | /api/date-roulette/spin | sanctum | Spin for an idea | SpinRequest | DateIdeaResource |
| GET | /api/date-roulette/recent | sanctum | Recent spins | - | DateIdeaResource collection |
| POST | /api/date-roulette/{id}/plan | sanctum | Turn an idea into a planner event | ScheduleIdeaRequest | PlannerEventResource |

## Business rules
1. **No AI, ever.** This is the free, instant, zero-cost fallback. Introducing a model call here would defeat its only purpose.
2. Available on the **free tier** with no cap.
3. Filters are budget band, energy, time available and whether a car is available. Unset filters mean 'anything'.
4. The wheel **avoids the last few ideas** the couple saw, so spinning twice does not give the same answer.
5. If filters match nothing, the app says so warmly and offers to loosen the strictest filter - it never returns an empty wheel.
6. An idea can become a **Planner event** in one tap, which is what makes this more than a toy.
7. Dietary and accessibility needs from both profiles filter the library.
8. The library is curated and reviewed for tone and inclusivity, like the challenges.

## AI usage (if any)
None, by design. See rule 1.

## Jobs, events & schedules
**Jobs:** none. **Events:** `DateRouletteSpun` (feeds the recap lightly). **Scheduled:** none.

## Mobile
**Screens:** Date Roulette (filter chips + spin), Result card, from the Dates tab and the quick-add sheet's 'Date idea'.

**Components:** `Chip`, `Button`, `Card`, `Sheet`, the wheel animation (respecting reduce-motion - it becomes a quick cross-fade).

**Navigation:** Dates tab, and quick-add.

**Offline:** the idea library is cached on the device, so the wheel spins with no network at all - which is often exactly when a couple is standing around deciding.

**Local notifications:** none.

## Permissions & privacy
`DateIdeaPolicy` - read-only library, shared. Spins are couple-scoped. Nothing private, nothing sent to AI.

## Tests
`api/tests/Feature/DateRoulette/` - **no AI call is made** (assert the fake driver records nothing); filters applied; immediate repeats avoided; empty result handled warmly rather than as an error; dietary/accessibility needs filter the library; free tier uncapped; one-tap planner creation.

Run: `composer test -- --filter=DateRoulette`

## Known gaps / TODO
- Library needs writing - target ~200 ideas spread across budget bands and energy levels, with a decent number of free and low-energy options.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
