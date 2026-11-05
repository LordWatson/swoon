# Love Notes 💕

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 6 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/love-notes/module.md`.
> Signature: 💕 Rose

## Purpose
Write a note now and schedule it to unlock later - 'open on our anniversary'. Delivered by a local notification on the partner's device once it unlocks.

## User stories
- As a partner, I can write a note and choose when it unlocks.
- As a partner, I get a notification when a note is ready to open.
- As an author, I can edit or cancel a note before it unlocks.
- As a recipient, I cannot see a note - or its contents - before its time.

## Data model
**love_notes** - id, couple_id, author_user_id, body, unlocks_at, timezone, opened_at (nullable), cancelled_at (nullable), deliver_local (bool), deliver_email (bool).

**Private to the author until `unlocks_at`:** the entire body, and - for a surprise note - the note's existence. This is the strictest privacy rule in the app after the gift vault.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/love-notes | sanctum | Own authored notes + unlocked received notes | - | LoveNoteResource collection |
| POST | /api/love-notes | sanctum | Write and schedule a note | StoreLoveNoteRequest | LoveNoteResource |
| PATCH | /api/love-notes/{id} | sanctum | Edit before unlock (author only) | UpdateLoveNoteRequest | LoveNoteResource |
| DELETE | /api/love-notes/{id} | sanctum | Cancel before unlock (author only) | - | 204 |
| POST | /api/love-notes/{id}/open | sanctum | Mark as opened | - | LoveNoteResource |
| GET | /api/love-notes/schedule | sanctum | Unlock times for local scheduling | - | LoveNoteScheduleResource |

## Business rules
1. **The body is never returned to the recipient before `unlocks_at`.** Not in a list, not in a detail response, not in a notification body. The server withholds it; the app never receives it.
2. **The schedule endpoint returns only the unlock time and the author** - no content - so the device can schedule a notification without ever holding the secret.
3. The notification body is deliberately contentless: 'A note from Sam is ready 💕'.
4. The **author** may edit or cancel freely before unlock, and not at all after.
5. Unlock time is stored with its timezone and honoured in the **recipient's** local time.
6. A note for a past date unlocks immediately rather than being lost.
7. An unopened unlocked note is gently resurfaced on Home - once, not repeatedly.
8. `deliver_email` sends the 'a note is ready' email, **never the note's text**, because email is not a place we control.
9. Cancelling a note before unlock removes it entirely; the recipient is never told a note existed.
10. Notes are excluded from AI prompts until opened, and are never quoted as AI output.

## AI usage (if any)
None. These are the couple's own words and the model has no business touching them.

## Jobs, events & schedules
**Jobs:** `SendLoveNoteReadyEmail` (content-free).

**Events:** `LoveNoteScheduled`, `LoveNoteUnlocked`, `LoveNoteOpened`.

**Scheduled:** `love-notes:unlock` every 15 minutes - marks due notes unlocked and queues the content-free email where opted in.

**Emails (Resend):** 'a note is ready' only.

## Mobile
**Screens:** Love Notes list (Scheduled by me / For me), Write note (body + unlock date picker), Note detail (locked state shows only 'from Sam, unlocks on 14 Feb'), Unlocked note with an opening animation.

**Components:** `Card`, `Sheet`, `Button`, `EmptyState`, lock badge, date picker.

**Navigation:** Us tab -> Love Notes, and the quick-add sheet's 'Note'.

**Offline:** writing works offline and queues. A locked note holds no content locally, so there is nothing cached to leak.

**Local notifications:** scheduled from `/love-notes/schedule`, body contentless, re-synced like all local notifications.

## Permissions & privacy
`LoveNotePolicy` - author: full control before unlock. Recipient: **no read of any kind before `unlocks_at`**, then read + open.

This is the module where a leak would be most felt, so the test suite asserts the absence of the body on every endpoint and in the notification payload. Never sent to AI while locked.

## Tests
`api/tests/Feature/LoveNotes/` - **body absent from every recipient-facing response before unlock**, including index, detail and the schedule endpoint; author edit/cancel allowed before and refused after; recipient timezone honoured; a past unlock date unlocks immediately; cancelled notes leave no trace for the recipient; the email contains no note text.

Run: `composer test -- --filter=LoveNotes`

## Known gaps / TODO
- Whether a note may carry a photo or voice note is undecided (it would be lovely, and would complicate the no-leak guarantee).
- No limit yet on how far ahead a note may be scheduled.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
