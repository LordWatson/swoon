# Shared Planner 🗓️

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 5 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/planner/module.md`.
> Signature: 🗓️ Apricot

## Purpose
The couple's shared calendar of things coming up - 'Brother's wedding, 21 June' - with categories, location notes, all-day or timed entries, optional reminders and a countdown. Scheduled dates from the AI planner land here too.

## User stories
- As a couple, we can add something coming up and see it counting down.
- As a couple, we can categorise events and see them on a simple list.
- As a partner, I can add an event offline.
- As a couple, a date we locked in appears here automatically.

## Data model
**planner_events** - id, couple_id, author_user_id, title, category (`date`|`family`|`travel`|`birthday`|`anniversary`|`admin`|`other`), starts_at, ends_at (nullable), is_all_day (bool), timezone, location_name (nullable free text), notes (nullable), source (`manual`|`date_plan`|`milestone`), source_id (nullable), deleted_at.

Indexed on (couple_id, starts_at). Nothing partner-private: the planner is shared by definition.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/planner-events | sanctum | Upcoming (and past) events | - | PlannerEventResource collection |
| POST | /api/planner-events | sanctum | Create an event | StorePlannerEventRequest | PlannerEventResource |
| GET | /api/planner-events/{id} | sanctum | Event detail | - | PlannerEventResource |
| PATCH | /api/planner-events/{id} | sanctum | Update an event | UpdatePlannerEventRequest | PlannerEventResource |
| DELETE | /api/planner-events/{id} | sanctum | Delete an event | - | 204 |

## Business rules
1. **Either partner may create, edit and delete** any planner event. The planner is genuinely shared - unlike moments, there is no author-only rule, because a shared calendar that only one person can fix is useless.
2. An **all-day** event has no time and must not drift across a timezone change. Store the date plus `is_all_day`, not a midnight timestamp.
3. A timed event stores its **own timezone**, so an event created for a trip abroad stays correct.
4. **Countdown is computed in the viewer's timezone** and shows days, or hours when under a day.
5. Events created from a chosen date plan carry `source = date_plan` and link back, so the date and its plan are never two unrelated things.
6. Deleting an event cancels its reminders.
7. Past events stay visible in a separate section - the planner is also a record of what happened.
8. Creating an event is possible **offline**, with a client ULID for idempotent sync.
9. `location_name` is free text. No coordinates, no map, no device location.

## AI usage (if any)
None directly. Upcoming events are an input to the Weekly Recap and Insights.

## Jobs, events & schedules
**Jobs:** none of its own.

**Events:** `PlannerEventCreated|Updated|Deleted` - Reminders listens and writes/cancels reminder rows. Milestones listens to avoid duplicating auto-generated yearly events.

**Scheduled:** none.

## Mobile
**Screens:** Planner tab (Upcoming / Past), Event detail, Add/Edit event sheet.

**Components:** `Card`, `Chip` (category), `Sheet`, `Button`, `EmptyState`, countdown display.

**Navigation:** Planner tab and the quick-add sheet's 'Plan'.

**Offline:** full create/edit offline with optimistic insert and Saving/Saved, same queue as Moments.

**Local notifications:** scheduled by the Reminders module from this module's events.

## Permissions & privacy
`PlannerEventPolicy` - both partners may do everything, scoped to their own couple. Nothing private, nothing sent to AI beyond titles/dates for the recap.

## Tests
`api/tests/Feature/Planner/` - either partner can edit; all-day events do not drift across timezones; countdown correctness; reminders cancelled on delete; `source = date_plan` linkage; offline idempotent create; another couple gets 404 on every route.

Run: `composer test -- --filter=Planner`

## Known gaps / TODO
- No device-calendar sync (read or write). Possibly wanted later; it is not in scope now.
- Recurring events are not supported - Milestones covers the yearly case, which is the main need.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
