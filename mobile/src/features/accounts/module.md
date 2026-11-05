# Accounts & Couple Linking 💞 - mobile

**Status:** planned
**Last updated:** 2026-11-05 - P1.T07: the shell is live. Three navigation states, five tabs, the quick-add sheet, and real sign-up / sign-in / invite screens.
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
- 2026-11-05 - P1.T07: shell. `navigation.ts` (the three states as a pure
  function), `AuthGate`, `useAuth` hooks, `token.ts` on expo-secure-store,
  typed `api.ts`, Welcome / Sign up / Sign in / Invite screens, five tabs with
  `PlaceholderScreen`, `QuickAddSheet`. 12 tests.
- 2026-11-05 - Created during project setup.
