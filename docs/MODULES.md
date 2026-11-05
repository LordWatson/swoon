# Modules — Swoon

**Last updated:** 2026-11-05
**Status key:** `planned` · `in progress` · `done` · `changed`

21 backend modules, 20 with a mobile feature folder. Each has a canonical
`module.md` in its backend folder — **that is the source of truth**, and it must
be updated in the same change as the code (see `CLAUDE.md`).

**Accounts is `done`** — Phase 1 complete: sign up with email, Apple or
Google, invite and accept, unlink, delete, export, and the five-tab shell.
Sixteen endpoints, 161 API tests and 136 mobile tests. No other module has
feature code yet.

---

## Index

| # | Module | Sig | Phase | Status | Canonical doc | Mobile |
|---|---|---|---|---|---|---|
| 1 | **Accounts & Couple Linking** | 💞 Rosé | 1 | done | [`Accounts`](../api/app/Modules/Accounts/module.md) | [`accounts`](../mobile/src/features/accounts/module.md) |
| 2 | **AI Platform** | ✨ Lavender | 2 | in progress | [`Ai`](../api/app/Modules/Ai/module.md) | — |
| 3 | **AI Date Planner** | 🍷 Rosé | 3 | planned | [`DatePlanner`](../api/app/Modules/DatePlanner/module.md) | [`date-planner`](../mobile/src/features/date-planner/module.md) |
| 4 | **Reminders** | ⏰ Apricot | 3 | planned | [`Reminders`](../api/app/Modules/Reminders/module.md) | [`reminders`](../mobile/src/features/reminders/module.md) |
| 5 | **Moments** | 📸 Apricot | 4 | planned | [`Moments`](../api/app/Modules/Moments/module.md) | [`moments`](../mobile/src/features/moments/module.md) |
| 6 | **On This Day** | 🕰️ Butter | 4 | planned | [`OnThisDay`](../api/app/Modules/OnThisDay/module.md) | [`on-this-day`](../mobile/src/features/on-this-day/module.md) |
| 7 | **Shared Planner** | 🗓️ Apricot | 5 | planned | [`Planner`](../api/app/Modules/Planner/module.md) | [`planner`](../mobile/src/features/planner/module.md) |
| 8 | **Milestones & Countdowns** | ⏳ Butter | 5 | planned | [`Milestones`](../api/app/Modules/Milestones/module.md) | [`milestones`](../mobile/src/features/milestones/module.md) |
| 9 | **Daily Spark** | 🔥 Butter | 6 | planned | [`DailySpark`](../api/app/Modules/DailySpark/module.md) | [`daily-spark`](../mobile/src/features/daily-spark/module.md) |
| 10 | **Romantic Challenges** | 💌 Rosé | 6 | planned | [`Challenges`](../api/app/Modules/Challenges/module.md) | [`challenges`](../mobile/src/features/challenges/module.md) |
| 11 | **Love Notes** | 💕 Rosé | 6 | planned | [`LoveNotes`](../api/app/Modules/LoveNotes/module.md) | [`love-notes`](../mobile/src/features/love-notes/module.md) |
| 12 | **Date Roulette** | 🎡 Rosé | 6 | planned | [`DateRoulette`](../api/app/Modules/DateRoulette/module.md) | [`date-roulette`](../mobile/src/features/date-roulette/module.md) |
| 13 | **Weekly Recap (AI)** | ✨ Lavender | 7 | planned | [`Recap`](../api/app/Modules/Recap/module.md) | [`recap`](../mobile/src/features/recap/module.md) |
| 14 | **Insights (AI)** | 💡 Lavender | 7 | planned | [`Insights`](../api/app/Modules/Insights/module.md) | [`insights`](../mobile/src/features/insights/module.md) |
| 15 | **Billing & Entitlements** | 🧾 Sage | 8 | planned | [`Billing`](../api/app/Modules/Billing/module.md) | [`billing`](../mobile/src/features/billing/module.md) |
| 16 | **Love Map / Profiles** | 🗺️ Apricot | 9 | planned | [`LoveMap`](../api/app/Modules/LoveMap/module.md) | [`love-map`](../mobile/src/features/love-map/module.md) |
| 17 | **Wishlist & Gift Vault** | 🎁 Apricot | 9 | planned | [`Wishlist`](../api/app/Modules/Wishlist/module.md) | [`wishlist`](../mobile/src/features/wishlist/module.md) |
| 18 | **Bucket List** | 🪣 Sage | 9 | planned | [`BucketList`](../api/app/Modules/BucketList/module.md) | [`bucket-list`](../mobile/src/features/bucket-list/module.md) |
| 19 | **Date Debrief** | 💬 Sage | 9 | planned | [`DateDebrief`](../api/app/Modules/DateDebrief/module.md) | [`date-debrief`](../mobile/src/features/date-debrief/module.md) |
| 20 | **Check-in (Temperature Check)** | 🌡️ Sage | 9 | planned | [`CheckIn`](../api/app/Modules/CheckIn/module.md) | [`check-in`](../mobile/src/features/check-in/module.md) |
| 21 | **Year in Moments** | 📖 Lavender | 11 | planned | [`YearInMoments`](../api/app/Modules/YearInMoments/module.md) | [`year-in-moments`](../mobile/src/features/year-in-moments/module.md) |

Two modules are not in the founder's list but are real modules with their own
docs: **Ai** (the AI platform, Phase 2) and **Billing** (entitlements, Phase 8).

---

## By phase

| Phase | Modules |
|---|---|
| 0 — Foundations | *(none — theme, tooling, CI)* |
| 1 — Accounts & Couples | Accounts |
| 2 — AI platform | Ai |
| 3 — AI Date Planner | DatePlanner, Reminders |
| 4 — Moments & On This Day | Moments, OnThisDay |
| 5 — Planner & Milestones | Planner, Milestones |
| 6 — Engagement | DailySpark, Challenges, LoveNotes, DateRoulette |
| 7 — AI Recap & Insights | Recap, Insights |
| 8 — Monetisation | Billing |
| 9 — More modules | LoveMap, Wishlist, BucketList, DateDebrief, CheckIn |
| 10 — Launch readiness | *(all — audit, security, store)* |
| 11 — High-ticket add-ons | YearInMoments |

---

## Modules that use AI

| Module | Prompt(s) | Notes |
|---|---|---|
| Ai | `safety_screen` | The platform itself; SafetyGuard lives here |
| DatePlanner | `date_plan` | The headline feature; biggest cost driver |
| Recap | `weekly_recap` | Weekly, per couple, local time |
| Insights | `insights` | At most one a week; silence is the default |
| YearInMoments | `year_in_moments` | Annual; basis of the printed book |
| Moments | `moment_tagging` | Optional suggestions only |
| Challenges | `date_pack` | Authoring only — reviewed before sale |
| DailySpark | `spark_question` | Authoring only — curated library |

**No AI at all:** Accounts, Reminders, OnThisDay, Planner, Milestones,
LoveNotes, Billing, LoveMap, BucketList, Wishlist (shared paths), and
**DateRoulette by design** — it is the zero-cost fallback.

---

## Modules holding permanently private data

These need the most careful review. A leak here is the product failing at its
central promise.

| Module | What is private | Visible to partner? |
|---|---|---|
| **CheckIn** | Every individual response, permanently | **Never**, at any setting |
| **Wishlist** | Gift vault items; wishlist claims | **Never** (vault) / hidden from the wish's owner (claims) |
| **LoveNotes** | Note body before unlock | Not until `unlocks_at` |
| **LoveMap** | Entries marked `private` | No |
| **Moments** | Moments marked `is_private` | No |
| **DatePlanner** | Each partner's answers | Not until both have answered |
| **DailySpark** | Each partner's answer | Not until both have answered |
| **DateDebrief** | Each partner's response | Not until both have responded |
| **Billing** | Orders (may be a surprise gift) | No |

See `docs/ARCHITECTURE.md` § 3.1 for the privacy classes and the two rules that
follow from them.

---

## Keeping this file honest

After any module change:

1. Update that module's `module.md` (**in the same change as the code**).
2. Update the **Status** cell above.
3. Add a line to `docs/SESSION_LOG.md`.
4. Tick the task in `docs/PLAN.md` and update **Current focus**.

The `.claude/hooks/check-module-docs.sh` Stop hook warns when module code
changed without its `module.md`. It is a reminder, not a gate — it will not stop
you shipping a doc-less change, so do not rely on it to care for you.
