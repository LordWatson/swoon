# Reminders ⏰

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 3 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/reminders/module.md`.
> Signature: ⏰ Apricot

## Purpose
Gets a couple to the thing they planned. Two channels only: on-device local notifications scheduled by the app when it syncs, and email via Resend from the Laravel scheduler for anything that must arrive when the app has not been opened. There are no remote push notifications.

## User stories
- As a partner, I get a nudge on my phone the day before a date.
- As a partner, I get an email reminder even if I have not opened the app.
- As a partner, I can choose how far ahead I am reminded, or turn it off.

## Data model
**reminders** - id, couple_id, remindable_type, remindable_id (planner event, date, milestone, love note), remind_at, channel (`local`|`email`), lead_minutes, email_sent_at (nullable), cancelled_at.

**reminder_preferences** - user_id, kind, lead_minutes, local_enabled, email_enabled. Per user, not per couple: partners may want different lead times.

Nothing partner-private; a reminder's existence is shared, its delivery preference is personal.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/reminders/schedule | sanctum | Everything the device should schedule locally | - | ReminderScheduleResource |
| GET | /api/reminder-preferences | sanctum | Own preferences | - | ReminderPreferencesResource |
| PUT | /api/reminder-preferences | sanctum | Update own preferences | UpdateReminderPreferencesRequest | ReminderPreferencesResource |

## Business rules
1. **No remote push notifications.** No APNs, no FCM, no device tokens stored. This is a deliberate architectural constraint, recorded in `docs/DECISIONS.md`.
2. **Local notifications are scheduled by the app on sync**, from `GET /api/reminders/schedule`. The server states intent; the device owns delivery.
3. The schedule endpoint returns a **complete desired state**, not a diff. The app cancels everything it previously scheduled for Swoon and re-schedules from the response - idempotent, and self-healing after a missed sync.
4. **Email is the fallback for anything that must land regardless** of whether the app has synced. Sent by the scheduler, at most once per reminder (`email_sent_at` makes it idempotent).
5. Reminder times are computed in the **user's own timezone**, not the couple's or the server's.
6. An all-day event reminds at a sensible local hour (09:00), not at midnight.
7. Cancelling or moving the underlying event cancels or moves its reminders.
8. A reminder never fires for something already past, after a clock change or a long offline period.
9. Reminder copy is warm and never nagging: 'Tomorrow: dinner at The Ivy 🍷'. No 'don't forget'.
10. Turning reminders off is honoured completely - no 'important' override.

## AI usage (if any)
None. Reminders are deterministic.

## Jobs, events & schedules
**Jobs:** `SendReminderEmail`.

**Events:** listens for `PlannerEventCreated|Updated|Deleted`, `DatePlanChosen`, `MilestoneCreated`, `LoveNoteScheduled` and writes/cancels reminder rows accordingly.

**Scheduled:** `reminders:dispatch` every 15 minutes - finds due email-channel reminders and queues them. Needs `php artisan schedule:work` running locally (Herd does not run it for you).

**Emails (Resend):** event reminder, date reminder, milestone reminder.

## Mobile
**Screens:** Reminder settings (inside Us tab). No screen of its own otherwise - reminders surface as notifications and as the countdown on Planner cards.

**`expo-notifications`, local only.** On app foreground and after any planner/date mutation: fetch the schedule, `cancelAllScheduledNotificationsAsync()` for our own identifiers, then re-schedule. Permission is requested **in context** - the first time the user schedules something - never on first launch.

**Offline:** this is the module that works best offline; once scheduled, notifications fire with no network at all.

**iOS caveat:** a hard cap of 64 pending local notifications. The app schedules the nearest 60 and re-fills on each sync.

## Permissions & privacy
A reminder's content is drawn from shared couple content only. **A scheduled Love Note's content never appears in the notification body** before its unlock time - only 'A note from Sam is ready 💕'.

No device tokens exist, so there is nothing to leak. Notification bodies avoid anything the lock screen should not show.

## Tests
`api/tests/Feature/Reminders/` - schedule endpoint returns complete desired state; timezone correctness across a DST boundary; email idempotency (`email_sent_at`); cancellation cascades; no reminder for a past event; preferences respected including fully off.

`mobile/src/features/reminders/__tests__/` - re-schedule is idempotent; the 64-notification cap is respected; permission is requested in context.

Run: `composer test -- --filter=Reminders` and `npm test -- reminders`

## Known gaps / TODO
- RESEND_API_KEY not set and the sending domain is not yet verified.
- Android background limits for exact local notification timing need testing on a real device.
- The iOS 64-notification strategy is untested at scale.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
