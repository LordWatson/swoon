# Moments 📸

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 4 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/moments/module.md`.
> Signature: 📸 Apricot

## Purpose
The couple's shared archive and the app's emotional centre of gravity. Either partner records a moment - text, optional photos, an optional short voice note, a place name, a date, a mood emoji and tags. Moments are what On This Day resurfaces, what the Weekly Recap draws on, and what the printed book is made of.

## User stories
- As a partner, I can capture a moment in a few seconds, with just text.
- As a partner, I can add photos or a short voice note.
- As a partner, I can tag a moment funny, sweet or a milestone.
- As a couple, we can scroll our whole history and search it.
- As a partner, I can record a moment with no signal and have it sync later.
- As a partner, I can undo a delete I did not mean.

## Data model
**moments** - id, couple_id, author_user_id, body (text), happened_on (date), place_name (nullable, free text - **never coordinates**), mood_emoji, is_private (bool, default false), deleted_at.

**moment_media** - id, moment_id, kind (`photo`|`voice`), disk, path, mime, bytes, duration_ms (voice), width, height (photo), processed_at (nullable), position.

**moment_tags** / **tags** - many-to-many; tags are couple-scoped so one couple's vocabulary never leaks to another.

**Private to one partner:** a moment with `is_private = true` is visible only to its author, shows the lock, and is excluded from shared AI output.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/moments | sanctum | Paginated feed, filterable by tag/date | - | MomentResource collection |
| POST | /api/moments | sanctum | Create a moment | StoreMomentRequest | MomentResource |
| GET | /api/moments/{id} | sanctum | Moment detail with signed media URLs | - | MomentResource |
| PATCH | /api/moments/{id} | sanctum | Edit own moment | UpdateMomentRequest | MomentResource |
| DELETE | /api/moments/{id} | sanctum | Soft delete (10s undo window) | - | 204 |
| POST | /api/moments/{id}/restore | sanctum | Undo a delete | - | MomentResource |
| POST | /api/moments/{id}/media | sanctum | Request an upload target | RequestUploadRequest | UploadTargetResource |
| DELETE | /api/moment-media/{id} | sanctum | Remove one media item | - | 204 |
| GET | /api/moments/search | sanctum | Search own + shared moments | - | MomentResource collection |

## Business rules
1. **A moment needs only text.** Everything else is optional. Capturing one is three taps from Home.
2. Either partner may create a moment; it belongs to the **couple**, and shows who added it (avatar + colour, never colour alone).
3. **Only the author may edit or delete** a moment. The partner can see it but not change it.
4. **Deletes are soft and undoable for 10 seconds** via a snackbar. The row disappears from the feed immediately; the delete commits when the window closes.
5. **Media lives on a private bucket and is served only through short-lived signed URLs** (default 15 minutes). There is no public media URL, ever.
6. Photos are processed into a display size and a thumbnail; the original is kept for the printed book.
7. Voice notes are capped (60 seconds) and transcoded to a single compact format.
8. **Media uploads are separate from the moment record**, so the text lands even while a photo is still uploading. A moment with a pending upload says so quietly.
9. **Moments are creatable offline** and sync later, with a 'Saving...' then 'Saved' state. A client-generated ULID makes the sync idempotent, so a retry never duplicates.
10. `place_name` is **free text the user typed**. No coordinates, no geocoding, no device location. A place name is a memory, not a position.
11. **Private moments** (`is_private`) are author-only, marked with the lock and 'Only you can see this', and excluded from any AI prompt producing shared output.
12. The free tier caps moments at 30 (`config('pricing.limits.spark.moments')`). Hitting it is a kind message; **existing moments are never deleted or hidden** because of a downgrade.
13. Search covers the author's own moments plus shared ones - never the partner's private moments.

## AI usage (if any)
Optional light tagging only: prompt **`moment_tagging` v1**, model key `light`, suggesting tags for a moment the user can accept or ignore. Never rewrites the couple's words. Private moments are excluded.

Moments are a major **input** to `weekly_recap`, `insights` and `year_in_moments`, always with private moments filtered out.

## Jobs, events & schedules
**Jobs:** `ProcessMomentPhoto` (resize, thumbnail, strip EXIF - including GPS tags, which matters here), `TranscodeVoiceNote`, `CommitMomentDeletion` (runs after the undo window), `SuggestMomentTags` (optional, light model).

**Events:** `MomentCreated` (feeds On This Day indexing and streaks), `MomentDeleted`.

**Scheduled:** `moments:purge-deleted` daily, hard-deleting soft-deleted moments and their media after 30 days.

## Mobile
**Screens:** Moments tab (feed), Moment detail, Add/Edit moment sheet, Media viewer, Search, Tag filter.

**Components:** `Card`, `AvatarPair`, `Chip`, `EmptyState`, `Sheet`, `Button`, snackbar for undo.

**Navigation:** Moments tab and the quick-add sheet's 'Moment'. Three taps from Home to a saved moment.

**Offline:** the core offline feature. Moments are written to a local queue with a client ULID, shown optimistically with 'Saving...', and pushed when connectivity returns. Media queues separately. A permanently failed item stays local and says so - nothing vanishes.

**Local notifications:** none directly (On This Day owns that).

## Permissions & privacy
`MomentPolicy` - view: either partner, unless `is_private` (author only). update/delete: author only. `MomentMediaPolicy` follows its moment.

**EXIF is stripped on upload, including GPS**, so a photo cannot reveal a location the product promises not to track.

Private moments are excluded from the partner's feed, search, On This Day, the Weekly Recap, Insights and the book. Signed URLs are short-lived and scoped to one object.

## Tests
`api/tests/Feature/Moments/` - create with text only; author-only edit and delete; **private moment invisible to the partner on every endpoint including search and On This Day**; undo window restores; commit after the window; media served only via signed URL and never publicly; **EXIF/GPS stripped**; free-tier cap is kind and non-destructive; idempotent create by client ULID.

`mobile/src/features/moments/__tests__/` - offline create then sync; no duplicate on retry; Saving/Saved states; undo snackbar.

Run: `composer test -- --filter=Moments`

## Known gaps / TODO
- S3 bucket is not yet provisioned; local dev uses the local disk.
- Voice note length cap (60s) is a guess and may need tuning.
- Search is a LIKE query to begin with; full-text or a search service may be needed as archives grow.
- No decision yet on whether a moment can be moved between couples after a re-link.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
