# Bucket List 🪣

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/bucket-list/module.md`.
> Signature: 🪣 Sage

## Purpose
A shared list of things to do together. The date planner can pull from it, and ticking one off creates a Moment - which is what turns a list into a record.

## User stories
- As a couple, we can keep a list of things we want to do together.
- As a couple, the date planner can suggest things from our list.
- As a couple, ticking something off becomes a moment we keep.
- As a couple, we can see how much of our list we have actually done.

## Data model
**bucket_list_items** - id, couple_id, added_by_user_id, title, notes, category, effort, budget_band, target_year (nullable), completed_at (nullable), completed_moment_id (nullable), position, deleted_at.

Couple-shared throughout; nothing private.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/bucket-list | sanctum | The list, with progress | - | BucketListItemResource collection |
| POST | /api/bucket-list | sanctum | Add an item | StoreBucketListItemRequest | BucketListItemResource |
| PATCH | /api/bucket-list/{id} | sanctum | Update an item | UpdateBucketListItemRequest | BucketListItemResource |
| DELETE | /api/bucket-list/{id} | sanctum | Remove an item | - | 204 |
| POST | /api/bucket-list/{id}/complete | sanctum | Tick it off (offers a Moment) | CompleteBucketListItemRequest | BucketListItemResource |
| POST | /api/bucket-list/{id}/uncomplete | sanctum | Undo | - | BucketListItemResource |

## Business rules
1. **Either partner may add, edit, reorder and complete** any item - it is a shared list, and shows who added each thing.
2. **Completing offers to create a Moment**, pre-filled with the item's title and today's date. It is offered, never silent, and the Moment is editable before saving.
3. Undoing a completion leaves the created Moment alone - the memory is real even if the tick was a mistake.
4. **Partner-visible items feed the date planner** as candidate ideas, so the list is not a dead document.
5. Progress is shown as 'done / total' and is **never framed as a shortfall** (UX rule 8). No 'you still haven't...'.
6. A `target_year` is a hope, not a deadline, and nothing nags about it.
7. Items are creatable offline with a client ULID.
8. No item may require location sharing or tracking.

## AI usage (if any)
No prompt of its own. Items are an allow-listed **input** to `date_plan`, so the planner can suggest something the couple already wants to do.

## Jobs, events & schedules
**Jobs:** none. **Events:** `BucketListItemCompleted` (feeds the Weekly Recap and optionally creates a Moment). **Scheduled:** none.

## Mobile
**Screens:** Bucket List (grouped by category, with progress), Add/Edit item sheet, Complete flow offering a Moment.

**Components:** `Card`, `Chip`, `Sheet`, `Button`, `ProgressBar`, `EmptyState`, `AvatarPair`, confetti on completion.

**Navigation:** Us tab -> Bucket List; surfaced in the date planner as 'from your list'.

**Offline:** full create/edit/complete offline, queued like Moments.

**Local notifications:** none.

## Permissions & privacy
`BucketListItemPolicy` - both partners may do everything within their couple. Nothing private. Titles and categories reach the planner prompt.

## Tests
`api/tests/Feature/BucketList/` - either partner may edit; completion offers but never silently creates a Moment; uncomplete leaves the Moment; items appear in the date planner payload; progress never rendered as a deficit; offline idempotent create; another couple gets 404.

Run: `composer test -- --filter=BucketList`

## Known gaps / TODO
- Reordering strategy (integer position vs fractional) is undecided; fractional indexing avoids rewriting rows on every drag.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
