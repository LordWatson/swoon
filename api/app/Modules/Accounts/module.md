# Accounts & Couple Linking 💞

**Status:** in progress
**Last updated:** 2026-11-05 - `users` table live on MySQL 8 with its profile columns and Sanctum tokens (P0.T10). No endpoints yet.
**Owner phase:** Phase 1 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/accounts/module.md`.
> Signature: 💞 Rose

## Purpose
Owns who a person is and who they are coupled with. Handles sign-up and sign-in (email + password, Sign in with Apple, Google), the partner invite and accept flow, and the Couple record that every other module hangs off. Also owns the exits: unlink, account deletion and data export.

## User stories
- As a new user, I can sign up with my email, Apple ID or Google account.
- As a user, I can invite my partner with a link or a short code.
- As an invited partner, I can tap a link and join our couple space.
- As a partner, I can see who I am linked with and when we linked.
- As a partner, I can unlink immediately, without my partner's consent.
- As a user, I can export everything I have ever put in, as a file.
- As a user, I can delete my account and have my data actually go.

## Data model
**users** - id, name, email (unique, nullable for Apple private relay), password (nullable for social-only), apple_sub, google_sub, avatar_path, colour (`rose`|`lavender`|`apricot`|`sage`), timezone, locale, date_of_birth (nullable, for birthday milestones), dietary_needs (json, feeds the planner), accessibility_needs (json), deleted_at.

> **Built so far (P0.T10).** `users` has `name`, `email` (unique, not yet
> nullable), `password`, `email_verified_at`, `remember_token`, `timezone`
> (default `Europe/London`), `locale` (default `en-GB`), `colour` (nullable
> until onboarding picks one - partners must differ, rule 12), `avatar_path`,
> `dietary_needs` (json), `accessibility_needs` (json), `date_of_birth` (date).
> `User` uses `HasApiTokens`, so `personal_access_tokens` is the live auth
> store. Cast types are declared as `@property` on the model because Larastan
> cannot read them off the schema.
>
> **Still to come in P1.T01:** `apple_sub`, `google_sub`, `deleted_at`, a
> nullable `email` and `password` for social-only accounts, and the `couples`,
> `couple_user` and `couple_invites` tables with the one-active-couple index.

**couples** - id, ulid (public id), linked_at, lock_in_mode (`both_heart`|`either_locks`, couple setting), anniversary_on (nullable), first_date_on (nullable), unlinked_at.

**couple_user** - couple_id, user_id, role (`a`|`b`, drives partner colour defaults), joined_at, left_at. A user has at most ONE row with left_at = null (one active couple per user), enforced by a partial unique index.

**couple_invites** - id, inviter_user_id, code (unique, 8 chars), email (nullable), expires_at, accepted_at, accepted_by_user_id, revoked_at.

Private to one partner: nothing in this module is partner-private except `users.password`, the social subject ids, and `date_of_birth` until the user chooses to share it for milestones.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| POST | /api/auth/register | guest | Email + password sign-up | RegisterRequest | UserResource + token |
| POST | /api/auth/login | guest | Email + password sign-in | LoginRequest | UserResource + token |
| POST | /api/auth/apple | guest | Sign in with Apple | AppleSignInRequest | UserResource + token |
| POST | /api/auth/google | guest | Sign in with Google | GoogleSignInRequest | UserResource + token |
| POST | /api/auth/logout | sanctum | Revoke the current token | - | 204 |
| GET | /api/me | sanctum | Current user + couple summary | - | MeResource |
| PATCH | /api/me | sanctum | Update profile basics | UpdateProfileRequest | UserResource |
| POST | /api/invites | sanctum | Create a partner invite | - | InviteResource |
| GET | /api/invites/{code} | guest | Preview an invite before accepting | - | InvitePreviewResource |
| POST | /api/invites/{code}/accept | sanctum | Accept and create the couple | - | CoupleResource |
| DELETE | /api/invites/{id} | sanctum | Revoke an outbound invite | - | 204 |
| GET | /api/couple | sanctum | Couple detail and settings | - | CoupleResource |
| PATCH | /api/couple | sanctum | Update couple settings (lock-in mode, dates) | UpdateCoupleRequest | CoupleResource |
| DELETE | /api/couple | sanctum | Unlink - immediate, no consent needed | - | 204 |
| POST | /api/me/export | sanctum | Request a data export | - | 202 + ExportResource |
| DELETE | /api/me | sanctum | Delete the account and its data | ConfirmDeletionRequest | 204 |

## Business rules
1. **One active couple per user.** Accepting an invite while already coupled is rejected with a clear message, not a silent re-link.
2. A couple is created **only** when the invite is accepted. Before that, the inviter is a solo user.
3. **Either partner can unlink, immediately, without the other's consent.** This is a safety requirement, not a convenience: requiring consent would let a controlling partner trap someone.
4. Unlinking **detaches** rather than deletes. Shared content stays with the couple record (now closed); each partner keeps what they personally authored. Neither partner can read the couple's shared content after unlink.
5. The other partner is told about the unlink by email, plainly and without blame. They are never told it is 'pending' - it has already happened.
6. Invite codes are 8 characters, expire after 14 days, and are single-use. A user holds at most one active outbound invite.
7. Invite codes avoid ambiguous characters (0/O, 1/I/l) because partners read them aloud.
8. An invite cannot be accepted by the user who created it.
9. **Account deletion is immediate** and cascades: own moments, answers, notes, media objects, tokens. Shared items authored by the other partner survive, with the deleted user's attribution reduced to a tombstone.
10. **Data export** covers everything the user authored plus the couple's shared content they could already see, as JSON + original media, delivered by a signed, expiring link. UK GDPR Article 20.
11. Sign in with Apple may return a private relay email, and may return the real name only on first authorisation - capture it then or never.
12. Partner colours must differ. If the joining partner's default clashes, they are assigned the other default and can change it.
13. **No location tracking of a partner, ever.** This module stores no location and exposes no partner-location endpoint.

## AI usage (if any)
None. Authentication and linking never involve the model.

## Jobs, events & schedules
**Jobs:** `SendInviteEmail`, `SendUnlinkNoticeEmail`, `BuildDataExport` (assembles JSON + media, uploads, emails a signed link), `PurgeDeletedUserData` (runs after the deletion response, so the API stays fast while the cascade completes).

**Events:** `CoupleLinked` (starts the 7-day trial, seeds milestones, seeds the first Daily Spark), `CoupleUnlinked`, `UserDeleted`.

**Scheduled:** `invites:prune` daily, removing expired invites.

**Emails (Resend):** invite, invite reminder (once, after 3 days), unlink notice, export ready, deletion confirmation.

## Mobile
**Screens:** Welcome, Sign up, Sign in, Onboarding (one question per screen), Invite partner (share sheet + copyable code), Waiting for partner, Accept invite (from deep link), Us tab, Couple settings, Privacy & data, Unlink confirm, Delete account confirm.

**Navigation:** unauthenticated users see the Welcome stack; a user without a couple sees the Invite/Waiting stack; a coupled user sees the five tabs. The deep link `swoon://invite/<code>` and the universal link `https://link.swoon.test/invite/<code>` both resolve to Accept invite, surviving a cold start and an app-install detour.

**Local state:** the token lives in `expo-secure-store`, never AsyncStorage. `useMe()` is the single source of auth state.

**Offline:** sign-in needs the network. Once signed in, the app opens to cached content and a quiet 'offline' chip.

## Permissions & privacy
`UserPolicy` - a user may only read/update themselves. `CouplePolicy` - both partners may view; both may update couple settings; both may unlink. A partner can **view** the other's profile basics and Love Map, and can **never** edit them.

Never exposed to the partner: password hash, social subject ids, auth tokens, email address (shown only as the masked form the inviter typed), raw `date_of_birth` unless shared for milestones.

Nothing in this module is sent to the AI.

## Tests
`api/tests/Feature/Accounts/UserTest.php` exists now: Sanctum token issuance,
the UK defaults, and a round-trip of the json and date casts. It runs green on
both SQLite and MySQL 8.

Still to write: registration, each social path, the full invite/accept happy path, and the edge cases that matter: accepting while already coupled, accepting your own invite, expired code, reused code, unlink from either side, deletion cascade, export completeness.

**Authorisation test per endpoint** - a second couple must receive 404/403 on every route here (Phase 10 makes this a standing requirement).

Run: `composer test -- --filter=Accounts`

## Known gaps / TODO
- Apple/Google client ids and the Apple private key are not yet configured.
- Universal-link association files (AASA, assetlinks.json) are not yet hosted.
- Export format not finalised - JSON shape needs to be decided and documented before Phase 1 ends.
- Re-linking with the same partner after an unlink: does old shared content come back? Currently no. Needs a product decision.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
- 2026-11-05 - P0.T10: `users` profile columns added to the base migration,
  `HasApiTokens` on `User`, first test file. Schema verified on MySQL 8.0.46.
