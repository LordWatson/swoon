# Accounts & Couple Linking 💞

**Status:** in progress
**Last updated:** 2026-11-05 - P1.T04: a couple can now be created end to end. Nine endpoints of sixteen.
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

> **Built so far (P1.T01).** The schema and the invariant are in. `users`
> gained `apple_sub`, `google_sub` and `deleted_at`, and `email` and `password`
> are now nullable — Apple may return a private-relay address or none, and a
> social-only account has no password. `couples`, `couple_user` and
> `couple_invites` exist, with `Couple` and `CoupleInvite` models,
> `CouplePolicy` and `UserPolicy`, factories (`Couple::factory()->linked()`,
> `->of($a, $b)`, `->unlinked()`) and a seeder that produces a linked couple
> plus a solo user.
>
> **The one-couple invariant is enforced by the database.** MySQL has no
> partial unique index, and `unique(user_id, left_at)` would not work because a
> UNIQUE index treats NULLs as distinct — two live memberships for one user
> would pass. Instead `couple_user.active_user_id` is a **virtual** generated
> column holding `user_id` while the membership is live and NULL once it ends,
> with a unique index on it. Three details matter: the expression is `CASE`
> rather than MySQL's `IF()` so SQLite accepts it too; the column is VIRTUAL,
> because MySQL refuses `ON DELETE CASCADE` on a base column of a *stored*
> generated column; and it is declared in `CREATE TABLE`, because SQLite cannot
> add a generated column by `ALTER TABLE`. `couple_invites.open_inviter_id`
> uses the same trick for "one open invite per user" (rule 6).
>
> **Previously (P0.T10).** `users` has `name`, `email` (unique, not yet
> nullable), `password`, `email_verified_at`, `remember_token`, `timezone`
> (default `Europe/London`), `locale` (default `en-GB`), `colour` (nullable
> until onboarding picks one - partners must differ, rule 12), `avatar_path`,
> `dietary_needs` (json), `accessibility_needs` (json), `date_of_birth` (date).
> `User` uses `HasApiTokens`, so `personal_access_tokens` is the live auth
> store. Cast types are declared as `@property` on the model because Larastan
> cannot read them off the schema.
>
> Nothing in that list is outstanding any more.

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

### Password policy

**Length, no composition rules.** Minimum 10 characters; nothing about symbols,
digits or mixed case. That is current practice (NIST SP 800-63B): composition
rules push people towards `Passw0rd!`, which is weaker than a long phrase.

No breached-password check. Laravel's `uncompromised()` rule calls
api.pwnedpasswords.com on every registration, which would put a third-party
network call in the sign-up path and in the test suite. A local blocklist is the
better answer if sign-up volume ever justifies one.

### How sign-in refuses

A wrong password and an unknown address return the **same** 422 with the same
message, and they take the same time to do it: when there is no account, the
Action hashes against a dummy instead of returning early. A login that answers
in 5 ms for unknown addresses and 90 ms for known ones is an account
enumerator whatever the message says — and for a couples app, "does this person
have an account" is itself private. Both halves are asserted in `AuthTest`.

### Social sign-in (P1.T03)

Apple and Google both hand the app an RS256-signed OIDC identity token, so the
verification is one shared `OidcTokenVerifier` and two thin wrappers.

**What is checked, and what happens if it is not.** The algorithm is pinned to
RS256 rather than read from the token's own header - otherwise `"alg": "none"`,
or HS256 signed with the public key as an HMAC secret, would pass. Audience and
issuer are matched against configured values, because a valid Google token
minted for somebody else's app is still a valid Google token. A nonce, when the
app sends one, must match and must not have been seen before: `Cache::add` is
atomic, so claiming the nonce *is* the replay check. **With no client id
configured, every token is refused** rather than every token accepted.

**Accounts are keyed on the provider's subject id, never on the email.** An
Apple user can change their relay address and a Google address can change
hands. `apple_sub` and `google_sub` are deliberately **not** fillable, so a
stray `User::create($request->all())` anywhere in the app can never hand
somebody another person's social identity; they are set with `forceFill` by
verified server code only.

**The reason a token was refused never reaches the caller.** Expired, forged,
wrong audience, replayed nonce and "nobody configured a client id" are all the
same 422 with the same message. The specific reason goes to the log, where a
developer debugging a deploy can have it.

**Private-relay addresses** are never matched against an existing account: a
relay address is specific to one person *and* one app, so it identifies nothing
outside it. Joining a social identity to an existing email account needs an
explicit `link_confirmed` from the person (ADR-023). Apple's **name** arrives
outside the token and only on first authorisation, so the client forwards it and
the Action backfills it - but never over a name the user has since chosen.

The provider's token is proof of identity for one request and is then thrown
away. What the app gets back is a Sanctum token of ours, exactly as from an
email sign-in.

### The invite flow (P1.T04)

**The concurrency guard is a conditional UPDATE, not the checks above it.** Two
people accepting the same code at the same moment would both pass a
read-then-write; `update(... where accepted_at is null)` can succeed only once,
and the loser is told the code has been used. The one-couple invariant in the
database is the backstop underneath that.

**Creating an invite revokes any earlier open one** rather than returning it.
The "one open invite" index counts an expired-but-open invite, so without the
revoke a user whose code ran out would be stuck with no way to get another.

**The preview endpoint is unauthenticated and returns a first name and nothing
else.** The code travels through messaging apps, so whatever it returns is
effectively public to whoever holds the link: no surname, no email, no avatar,
no user id, no couple. That is why `InvitePreviewResource` is a separate class
rather than a flag on `InviteResource`. It is rate-limited per IP so the short
code cannot be ground through in bulk, and a malformed code 404s exactly like a
non-existent one.

**Refusals, each with a plain message:** your own invite, already coupled,
expired, used, revoked, and *the inviter has since linked with somebody else*
— the last being the case a careless implementation creates a second couple for.

The reminder is one email, three days in, and `reminded_at` is nullable and set
once, which is what makes "a single reminder" true rather than a promise.

### Rate limiting

Two limits on the auth routes, and the second one's key is the point:

| Limit | Key | Why |
|---|---|---|
| 20/min | IP | Slows credential stuffing, which sprays many addresses from few hosts |
| 5/min | **email + IP** | Tighter limit for one account — but keyed on the IP too |

Keying the email limit on the address **alone** would let a stranger lock a real
person out of their own account by failing to log in as them five times: a cheap
denial of service against the victim. Keyed on email+IP, an attacker only ever
throttles themselves. Asserted in `AuthTest`.

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

**How that is enforced (P1.T02).** `PartnerResource` is a separate class from
`UserResource`, not a set of conditionals inside one shape. A privacy gate
written as an `if` in a shared resource is one careless edit from leaking;
a resource that never mentions `email` cannot leak it. `apple_sub` and
`google_sub` are on the model's `#[Hidden]` list as well, so they cannot escape
through a stray `toArray()` either.

Nothing in this module is sent to the AI.

## Tests
`api/tests/Feature/Accounts/UserTest.php` exists now: Sanctum token issuance,
the UK defaults, and a round-trip of the json and date casts. It runs green on
both SQLite and MySQL 8.

Still to write: registration, each social path, the full invite/accept happy path, and the edge cases that matter: accepting while already coupled, accepting your own invite, expired code, reused code, unlink from either side, deletion cascade, export completeness.

**Authorisation test per endpoint** - a second couple must receive 404/403 on every route here (Phase 10 makes this a standing requirement).

Built in P1.T01: `tests/Feature/Accounts/CoupleTest.php` (the invariant,
including two concurrent accepts and a third-member attempt) and
`PolicyTest.php` (either partner equal, stranger refused, other couple refused,
and **ex-partner refused after unlink** - the case a "was ever a member" check
gets wrong).

Run: `composer test -- --filter=Accounts`

## Known gaps / TODO
- Apple/Google client ids and the Apple private key are not yet configured, so
  **no live sign-in has been exercised** - only the full verification path
  against a stubbed JWKS. Set `APPLE_CLIENT_ID` and the three
  `GOOGLE_CLIENT_ID_*` values; until then both endpoints correctly refuse
  everything.
- Universal-link association files (AASA, assetlinks.json) are not yet hosted.
- Export format not finalised - JSON shape needs to be decided and documented before Phase 1 ends.
- Re-linking with the same partner after an unlink: does old shared content come back? Currently no. Needs a product decision.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
- 2026-11-05 - P0.T10: `users` profile columns added to the base migration,
  `HasApiTokens` on `User`, first test file. Schema verified on MySQL 8.0.46.
- 2026-11-05 - P1.T04: invite, preview, accept, revoke. `CreateInvite`,
  `AcceptInvite`, `RevokeInvite`, `CoupleLinked`, `InviteMail`,
  `SendInviteEmail`, `invites:remind`. 29 tests including two simultaneous
  accepts of one code.
- 2026-11-05 - P1.T03: Sign in with Apple and Google. `OidcTokenVerifier`,
  `SocialIdentityProvider`, `FindOrCreateSocialUser`. 19 tests against a
  throwaway keypair and a stubbed JWKS, forgeries included; nothing touches the
  network. ADR-022 (firebase/php-jwt), ADR-023 (explicit linking).
- 2026-11-05 - P1.T02: register / login / logout / me. Actions, Form Requests,
  `UserResource`, `PartnerResource`, `CoupleResource`, `MeResource`. Non-
  enumerating sign-in with equalised timing; rate limits keyed so a stranger
  cannot lock someone out. 18 tests.
- 2026-11-05 - P1.T01: `couples`, `couple_user`, `couple_invites`; `Couple` and
  `CoupleInvite` models; `CouplePolicy` + `UserPolicy`; factories and seeder.
  The one-couple invariant is a unique index on a virtual generated column,
  proven by a test that inserts straight into the table. 22 tests.
