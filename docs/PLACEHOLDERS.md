# What is real and what is not

**Last updated:** 2026-11-05

This file exists because of a fair question — *"how am I supposed to know
what's hardcoded and what isn't?"* — to which the previous answer was "ask
Claude", which is not an answer. The Home screen shipped showing somebody
else's name and invented statistics, and nothing in the repository would have
told you.

So: every screen in the app is listed below, and
**`mobile/src/app/__tests__/inventory.test.tsx` fails if a screen exists that
is not listed.** A new screen cannot be added without declaring what it is,
the same way a new API route cannot be added without declaring who may reach
it. That test is the one that bites; this file is the readable version.

## Status key

| Status | Means |
|---|---|
| **live** | Everything it shows comes from the API or the device. Nothing is invented. |
| **placeholder** | The feature does not exist yet. It says so, shows no invented data, and offers no button that does nothing. |
| **shell** | Layout and navigation only. Renders no content of its own. |

## Mobile screens

| Screen | Status | Notes |
|---|---|---|
| `_layout.tsx` | shell | Fonts, providers, the auth gate |
| `welcome.tsx` | live | Static brand copy; both buttons navigate |
| `sign-up.tsx` | live | `POST /api/auth/register` |
| `sign-in.tsx` | live | `POST /api/auth/login` |
| `onboarding.tsx` | live | PATCHes each answer to `/api/me` as it is given |
| `invite.tsx` | live | Creates, shares and nudges a real invite |
| `invite/[code].tsx` | live | Previews and accepts a real code |
| `(tabs)/_layout.tsx` | shell | Five tabs, Phosphor icons, quick-add sheet |
| `(tabs)/index.tsx` | live | Greeting, names, days together, avatars — all from `GET /api/me` |
| `(tabs)/dates.tsx` | **placeholder** | Arrives in **Phase 3** |
| `(tabs)/moments.tsx` | **placeholder** | Arrives in **Phase 4** |
| `(tabs)/planner.tsx` | **placeholder** | Arrives in **Phase 5** |
| `(tabs)/us.tsx` | live | Couple summary and sign-out from `/api/me`. Settings, unlink and export are Phase 5 |

## Things that are real but temporary

Not placeholders — they work, and they will be replaced by something better.

| What | Where | Until |
|---|---|---|
| **The entitlement in `/api/me`** is a stub: everyone is `spark`, `is_paid: false`. True today (nobody can pay yet) and the shape is final, so feature gates can be written against it. | `MeResource::entitlement()` | Phase 8 |
| **The quick-add sheet's four options** each land on the tab that will own them, rather than a creation screen. The tab says which phase brings it. | `(tabs)/_layout.tsx` | Phases 3–6 |
| **Home's "what we are building next" card** names the three features coming, in words. It goes when the first of them lands. | `(tabs)/index.tsx` | Phase 3 |
| **"Plan a date" on Home** goes to the Dates tab, not a planner. | `(tabs)/index.tsx` | Phase 3 |
| **The Us tab** shows the couple summary and sign-out only. Settings, unlink and export are named but not built. | `(tabs)/us.tsx` | Phase 5 |

## Screens specified but not built at all

Written up in `api/app/Modules/Accounts/module.md`, including the exact copy
they must carry, but with no file yet. They are **not** in the inventory,
because a screen that does not exist cannot mislead anyone.

| Screen | Needed for | Required copy |
|---|---|---|
| Unlink confirm | P1.T08 | Must state what happens to the data **and** to the subscription |
| Privacy & data | P1.T09 | Export and delete, one plain confirm each |

## What the gate actually checks

1. **Every screen has a declared status.** A new file fails the suite by name.
2. **No stale entries** — a deleted screen cannot leave a claim behind.
3. **Every placeholder renders `PlaceholderScreen`**, which by construction has
   no action button and states its phase. This is what stops a placeholder
   quietly growing realistic-looking content, which is exactly what happened
   to Home.
4. **No screen wires a button to nothing.** `onPress={() => undefined}` is how
   four tabs ended up with buttons that did nothing when tapped.
5. **No screen contains a person's name as a literal.** The specific names that
   were hardcoded are checked for by name.

Comments are stripped before the literal checks, so a comment explaining why a
name was removed is not itself a violation. It is a backstop, not a proof: a
determined hardcoded string can still get through. It catches the mistake that
actually happened.

## When you build a feature

1. Replace the placeholder screen.
2. Change its row here to **live**.
3. Change its status in `inventory.test.tsx`.
4. Delete its line from "things that are real but temporary" if it had one.

The suite will tell you if you forget step 3.
