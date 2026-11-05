# Accounts & Couple Linking 💞 - mobile

**Status:** done
**Last updated:** 2026-11-05 - fixed: sign-in and sign-out did not redirect, and the onboarding flow was unreachable.
**Owner phase:** Phase 1 (see docs/PLAN.md)

> **Canonical doc:** [`api/app/Modules/Accounts/module.md`](../../../../api/app/Modules/Accounts/module.md)
> Read that first. This file covers only what is mobile-specific.
> Signature: 💞 Rose

## Screens, navigation, components
**Screens:** Welcome, Sign up, Sign in, Onboarding (one question per screen), Invite partner (share sheet + copyable code), Waiting for partner, Accept invite (from deep link), Us tab, Couple settings, Privacy & data, Unlink confirm, Delete account confirm.

**Navigation:** unauthenticated users see the Welcome stack; a user without a couple sees the Invite/Waiting stack; a coupled user sees the five tabs. The deep link `swoon://invite/<code>` and the universal link `https://link.swoon.test/invite/<code>` both resolve to Accept invite, surviving a cold start and an app-install detour.

**Local state:** the token lives in `expo-secure-store`, never AsyncStorage. `useMe()` is the single source of auth state.

**Offline:** sign-in needs the network. Once signed in, the app opens to cached content and a quiet 'offline' chip.

## The API this feature talks to (ready as of P1.T02)

No mobile code yet — these are the shapes the screens in P1.T06 and P1.T07 get
to build against, rather than guess at.

| Call | Returns |
|---|---|
| `POST /api/auth/register` | `201 { user, token }` |
| `POST /api/auth/login` | `200 { user, token }` |
| `POST /api/auth/logout` | `204`, revoking **only** this device's token |
| `GET /api/me` | `{ user, couple, entitlement }` |

Responses are **flat** — no `data` wrapper anywhere (`JsonResource::withoutWrapping()`).

`GET /api/me` is the single source of which navigation state the app is in:
`couple === null` means signed in but solo, so show the Invite stack rather than
the tabs. `entitlement` is a stub until Phase 8 but has its final shape, so
feature gates can be written now.

Sign-in failures are a 422 with one message for every cause, by design — the
app must not try to explain *why* it failed beyond what the server says, since
the whole point is that the server does not know either, as far as the caller
is concerned.

## The shell (P1.T07)

**Three navigation states, one pure function.** `authStateFor()` in
`navigation.ts` is the only thing that decides, and it is a pure function so
the rule can be tested without driving a navigator:

| State | Route | When |
|---|---|---|
| `loading` | — | the token is still being read from secure storage |
| `unauthenticated` | `/welcome` | no token, **or** a token the API refused |
| `solo` | `/invite` | signed in, `couple === null` |
| `coupled` | `/(tabs)/index` | signed in and linked |

`loading` exists so the app does not flash Welcome at someone who *is* signed
in, on every single launch. "A token the API refused" matters too: a token can
be revoked from another device, and the app must not sit on a dead one for ever.

**A solo user never sees the couple tabs.** That is a privacy rule as much as a
routing one — every tab is about two people, half would have nothing to render,
and some would imply a partner who does not exist. The decision comes from the
server's `couple: null`, never from anything the app infers.

**Five tabs and a centre "+". No hamburger, ever** (UX rule 14). The "+" floats
above the tab bar rather than being a sixth tab, because it is an action, not a
place — and a tab that does not navigate confuses screen-reader users most of
all. It carries a real `accessibilityLabel`.

The quick-add sheet has **exactly four** options, as a `const` array with a
test asserting its length: four is the number a person can read without
deciding, and a fifth is a product decision rather than a convenience.

Tabs that have their shell but not their feature render a `Hero` and a real
`EmptyState` (`PlaceholderScreen`), because a tab rendering nothing looks
broken rather than early.

## Onboarding (P1.T06)

Four questions, **one per screen** (UX rule 3): confirm your name, confirm your
timezone, then dietary needs and accessibility needs as chips. The step list in
`onboarding.ts` *is* the order — nothing else derives it — and the rules are
pure functions so they can be asserted without driving a navigator.

**Each answer is PATCHed as it is given**, so a person who gives up halfway
keeps what they got through. That is also what makes "back never loses an
answer" true on the server and not just in the client.

**The draft lives in `expo-secure-store`, deliberately.** It holds dietary and
accessibility needs, which can reveal religion or disability — leaving that in
plain-text storage while the flow is half-finished would be worse than leaving
it in memory. The draft exists only so re-opening a backgrounded app does not
blank the screen you were on.

Both optional questions offer **"No preference"**, and an empty list is stored
as a real answer rather than left unset — otherwise the app asks again next
launch.

The timezone is detected rather than asked, then confirmed: everything the app
schedules is rendered in the user's own time, and getting it right silently is
a better first impression than a question.

## Waiting for a partner

Once a code exists, the invite screen becomes the waiting screen: both avatars
with the partner's dimmed (`AvatarPair showPending`), and a **nudge**.

The nudge's cooldown is the **server's** (`POST /api/invites/{code}/remind`,
24 h from `config('swoon.nudges.cooldown_hours')`). A client-side timer is
advisory, and the entire point of a cooldown is that it cannot become
pestering — so the button is always offered and the server's refusal is what
the person reads. It shares `reminded_at` with the scheduled three-day
reminder, so a manual nudge suppresses the automatic one rather than both
arriving.

## Onboarding (P1.T06)

Four questions, **one per screen** (UX rule 3): confirm your name, confirm your
timezone, then dietary needs and accessibility needs as chips. The step list in
`onboarding.ts` *is* the order, and the rules are pure functions so they can be
asserted without driving a navigator.

**Each answer is PATCHed as it is given**, so a person who gives up halfway
keeps what they got through. That is what makes "back never loses an answer"
true on the server as well as in the client.

**The draft lives in `expo-secure-store`, deliberately.** It holds dietary and
accessibility needs, which can reveal religion or disability - leaving that in
plain-text storage while the flow is half-finished would be worse than leaving
it in memory. The draft exists only so re-opening a backgrounded app does not
blank the screen you were on.

Both optional questions offer **"No preference"**, and an empty list is stored
as a real answer rather than left unset - otherwise the app asks again next
launch.

## Waiting for a partner

Once a code exists the invite screen becomes the waiting screen: both avatars
with the partner's dimmed (`AvatarPair showPending`), and a **nudge**.

The nudge's cooldown is the **server's** (`POST /api/invites/{code}/remind`,
24 h from `config('swoon.nudges.cooldown_hours')`). A client-side timer is
advisory, and the entire point of a cooldown is that it cannot become
pestering - so the button is always offered and the server's refusal is what
the person reads. It shares `reminded_at` with the scheduled three-day
reminder, so a manual nudge suppresses the automatic one rather than both
arriving.

## Deep links and universal links (P1.T05)

Both forms land on `app/invite/[code].tsx`:

- `swoon://invite/<code>` — the custom scheme, works in a dev build with no
  hosting at all.
- `https://link.swoon.test/invite/<code>` — needs the two association files
  (`docs/DEV_TOOLING.md` § 4.1), which are **not hosted yet**.

**Parsing is a pure function** (`inviteLink.ts`), because this is the one part
of the app that genuinely cannot be checked by tapping around: a cold start
from a link, and a link followed before the app existed, are both
unreproducible by hand. It is lenient about what messaging apps do to a URL —
trailing slashes, tracking parameters, fragments, lower case — and strict about
the code, so a malformed one is rejected before it becomes an API call.

**The install detour** is the sequence this was built for: tap the link, have
no app, visit the store, install, open — and the cold start carries **no URL**.
So the code is stashed the moment it is seen (`pendingInvite.ts`) and resolved
once there is an account to attach it to, which happens on the invite screen.
It is kept in `expo-secure-store` because the code is a capability: anyone
holding it can join a couple's space. It is validated on the way out as well as
in, and cleared as soon as it is used.

A dead code gets a **warm, specific message** — expired, or not a code at all —
rather than a 404 screen. The person reading it did nothing wrong: their
partner's code simply ran out.

`inviteCode.ts` mirrors the server's alphabet (no 0/O, no 1/I/L). Duplicated
rather than fetched, so a typo is caught before a round trip — squinting at a
text message and then waiting for a request to tell you the length is wrong is
a bad experience.

## Session state must be reactive (fixed 2026-11-05)

**The bug:** registering created the account, saved the token, and left the
person on the sign-up screen. Nothing was broken on the server — the gate
simply never recomputed, because token presence was read **once** into
`useState` on mount. Saving a token later changed nothing anybody was watching.

Token presence is now a **query** (`['auth-token']`), so it is shared, cached
and invalidatable. `refreshSession()` invalidates the token and the `me` keys
together, and every mutation that signs in or out calls it — one helper,
because forgetting the token key is precisely the bug.

Sign-out had the same defect in mirror image: `queryClient.clear()` empties the
cache but does **not** re-render mounted observers, so the gate kept showing
the tabs to someone who had signed out. It now invalidates first, then removes
the previous person's data.

`useMe` is gated on having a token, so a cold start with no session no longer
fires a guaranteed 401.

> **The lesson for later phases:** anything the navigation gate reads has to
> live somewhere observable. Component state that is seeded once from storage
> looks right in a screenshot and never updates.

## The onboarding flow was unreachable (fixed 2026-11-05)

Found while fixing the above. The gate asked `current.startsWith('/invite')`
for a solo user, so `/onboarding` was bounced to `/invite` — the flow built in
P1.T06 could never be shown. The routes each state may occupy are now **data**
(`ALLOWED_ROUTES`), with a test per state, and sign-up sends a newly registered
person to onboarding explicitly. Someone signing *in* skips it, having already
answered.

One trap worth knowing: `'/'` cannot be matched with `startsWith`, because it
matches every path and would disable the gate completely. There is a test for
that too.

## Where the token lives

`expo-secure-store`, never AsyncStorage: the token is the whole account, and
AsyncStorage is plain text any backup or file-level compromise reads. The API
client knows nothing about storage — it is handed a reader at startup
(`setTokenReader`), which is why `src/api/client.ts` stays dependency-free and
trivially testable.

The device timezone comes from `Intl.DateTimeFormat().resolvedOptions()`, not
`expo-localization`: a package for one line the platform already answers. If
it is unavailable the field is omitted and the server default stands, which is
why the API treats `timezone` as optional.

## Local state
Server state goes through TanStack Query, keyed per module. Zustand is used only
where genuinely client-only state must outlive a screen (for example, an
in-progress questionnaire). Auth tokens live in `expo-secure-store`.

## Offline behaviour
See the Mobile section above and `docs/UX_RULES.md` rule 11. Anything creatable
offline uses a client-generated ULID so a retried sync cannot duplicate it.

## Theme usage
Signature colour **Rose**, emoji 💞 (see `mobile/src/theme/modules.ts`).
No raw hex or font names in this folder - tokens only, enforced by the
`swoon/no-raw-design-values` ESLint rule.

## Tests
`mobile/src/features/accounts/__tests__/` - Jest + React Native Testing
Library. Cover: the screens render from the theme, offline queueing where it
applies, and any reveal/privacy gate as seen from the client.

## Known gaps / TODO
- Not implemented yet. See the canonical doc's gaps list.

## Changelog
- 2026-11-05 - **Fix:** registering and signing in did not redirect. Token
  presence was component state seeded once on mount, so nothing recomputed.
  It is a query now, invalidated with `me` by one `refreshSession()` helper.
  Sign-out had the mirror bug (`clear()` does not re-render observers). Also
  found and fixed: the gate made `/onboarding` unreachable. 11 new tests.
- 2026-11-05 - P1.T05: deep and universal links. `inviteLink.ts`,
  `inviteCode.ts`, `pendingInvite.ts`, `app/invite/[code].tsx`, and the
  pending-code pickup on the invite screen. 16 tests. Phase 1 complete.
- 2026-11-05 - P1.T06: onboarding (`onboarding.ts`, `useOnboardingDraft`,
  `onboarding.tsx`), the waiting screen and the server-side nudge. Needed two
  API endpoints that did not exist yet. 10 tests.
- 2026-11-05 - P1.T06: onboarding (`onboarding.ts`, `useOnboardingDraft`,
  `onboarding.tsx`), the waiting screen and the server-side nudge. Needed two
  API endpoints that did not exist yet: `PATCH /api/me` and
  `POST /api/invites/{code}/remind`. 10 tests.
- 2026-11-05 - P1.T07: shell. `navigation.ts` (the three states as a pure
  function), `AuthGate`, `useAuth` hooks, `token.ts` on expo-secure-store,
  typed `api.ts`, Welcome / Sign up / Sign in / Invite screens, five tabs with
  `PlaceholderScreen`, `QuickAddSheet`. 12 tests.
- 2026-11-05 - Created during project setup.
