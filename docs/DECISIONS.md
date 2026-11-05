# Decisions — Swoon

ADR-style log. **Newest first.** One entry per non-obvious decision.

Add an entry whenever you choose between real alternatives, deviate from a spec,
add a dependency, or make a call a future session would otherwise re-litigate.
Keep each entry short: context, decision, consequence.

---

## ADR-018 — The log tail is its own script, not part of `composer dev`
**Date:** 2026-11-05 · **Status:** accepted

**Context.** Laravel's default `composer dev` runs the queue worker, the
scheduler and `php artisan pail` under `concurrently --kill-others`. Pail
requires the `pcntl` extension, which Windows PHP does not provide — so on the
machine this project is built on, Pail exited 1 immediately and `--kill-others`
then killed the worker and the scheduler with it. `composer dev` had never once
worked here, and P0.T01's acceptance criterion claiming it did was never
actually run to completion.

**Decision.** `composer dev` is the queue worker and the scheduler, both of
which work everywhere. The log tail is `composer logs`, documented alongside
the Windows equivalent (`Get-Content … -Wait`). `--kill-others` stays: with
only two processes that must both be up, one dying should stop the other.

**Consequence.** Two terminals instead of one if you want a live log on
mac/linux. In exchange the dev loop works on the platform it is used on. Do not
put Pail back into `composer dev` — it is not a missing feature, it is a
platform incompatibility that takes the whole loop down with it.

---

## ADR-017 — SafetyGuard has no off switch
**Date:** 2026-11-05 · **Status:** accepted

**Context.** `config/ai.php` carried `'safety' => ['enabled' => env('AI_SAFETY_ENABLED', true)]`,
written before any AI code existed. `CLAUDE.md` and `Ai/module.md` both describe
SafetyGuard as running on every user-facing generation and failing closed, and
list it as non-negotiable. A flag made that one environment variable away from off.

**Decision.** No `enabled` key. SafetyGuard always runs. Work that must not
reach a model uses `AI_DRIVER=fake`, which is the honest lever: it replaces the
provider rather than skipping the safety step.

**Consequence.** There is no supported way to turn safety off, including in an
incident. If the guard itself becomes the problem, the fix is to fix the guard
or stop generating — both of which fail closed, as intended.

---

## ADR-016 — AI prices in US cents, every budget in pence, converted explicitly
**Date:** 2026-11-05 · **Status:** accepted

**Context.** `config/ai.php` held provider prices as US-dollar floats
(`'input' => 4.00`) directly beside caps in pence
(`couple_monthly_pence => 500`), with no exchange rate anywhere in the repo.
`config/pricing.php` meanwhile states "prices are in pence so they are never
floats". The first code to compare the two would have been wrong by roughly
100× and by the exchange rate, and the error would have shown up as a real bill.

**Decision.** Rename the key to `pricing_usd_cents_per_mtok` so the unit
travels with the value, hold whole numbers only, and add one
`usd_to_pence` rate (default 80) that cost logging must apply before comparing
anything to a cap. The unit goes in the key name, not in a comment above it.

**Consequence.** Cost logging gains one multiplication and must never skip it.
The rate is approximate and deliberately conservative — it decides when
spending stops, so erring high stops us a little early rather than a lot late.
Revisit when the exchange rate moves far.

---

## ADR-015 — The product name reaches Laravel's own config through a provider
**Date:** 2026-11-05 · **Status:** accepted

**Context.** ADR-001 puts the working name in `config/swoon.php` and
`mobile/src/config/app.ts` only. Laravel's markdown mail theme prints
`config('app.name')` as the header and footer of every email, and notifications
take their sender name from `mail.from.name` — neither reads `swoon.name`, and
neither passes through `App\Mail\SwoonMailable`. The first attempt coupled them
in the env file with `APP_NAME="${SWOON_NAME}"`. Dotenv substitutes only
variables defined **earlier in the same file** and otherwise leaves the literal
text `${SWOON_NAME}`, which was verified against the project's own Dotenv: a
production env assembled in another order would have put that string in the
header of a real invite email.

**Decision.** `AppServiceProvider::boot()` sets `app.name` and
`mail.from.name` from `config('swoon.name')`. `APP_NAME` stays a plain literal,
where its value only feeds the cache prefix, the Redis prefix and the session
cookie name.

**Consequence.** ADR-001 still holds, and now covers Laravel's own email too.
Anything resolved at config-load time rather than at render time — those three
prefixes — still reads `APP_NAME`, so renaming the product means changing
`SWOON_NAME` and `APP_NAME` in the env, with `config/swoon.php` as the value
everything user-facing actually reads.

---

## ADR-014 — Graphify output: commit the report, ignore the cache
**Date:** 2026-11-05 · **Status:** accepted

**Context.** `graphify-out/` holds both a generated cache and human-readable
reports. The session protocol tells future sessions to read
`graphify-out/GRAPH_REPORT.md` to orient before grepping, which only works if
that file is in the repository.

**Decision.** Commit `graphify-out/` **except** `graphify-out/cache/`, which is
gitignored. The graph report and index are small, useful in review, and are the
first thing a new session reads.

**Consequence.** The report can go stale between refreshes, so step 6 of the
session protocol requires refreshing it after structural changes. A stale graph
is worse than none, because it is trusted.

---

## ADR-013 — Agent Skills installed as a plugin, not vendored into the repo
**Date:** 2026-11-05 · **Status:** accepted (resolved same day)

**Context.** The brief asks that Ponytail and Agent Skills both be active. At the
start of the session only `ponytail@ponytail` (v5.3.3) was installed; the
`addy-agent-skills` marketplace was registered in `~/.claude/settings.json` but
no plugin from it was enabled. Two routes existed: install the plugin properly,
or vendor the skills into project-level `.claude/skills/` with
`npx skills add addyosmani/agent-skills --agent claude-code`.

**Decision.** Installed the **plugin** at user scope:

```
claude plugin install agent-skills@addy-agent-skills
```

`agent-skills@addy-agent-skills` **v0.6.15** — 25 skills, 9 slash commands and 4
subagents covering spec → plan → build → verify → review → ship, which is
exactly the lifecycle the session protocol names.

The vendoring fallback was **not** used. Copying 25 skills into
`.claude/skills/` would freeze them at today's version, add ~25 directories of
third-party content to our diffs and reviews, and leave us hand-merging upstream
changes. A plugin updates in place.

**Consequence.** The plugin is at **user scope**, so it is not automatically
present for anyone else who clones this repository. Two follow-ups:

1. A teammate runs the same `claude plugin install` command — documented in
   `docs/DEV_TOOLING.md` § 8.
2. Better: declare the marketplace in the project's `.claude/settings.json`
   under `extraKnownMarketplaces`, which is what that key is for. Not done yet —
   writing that file was blocked during this session (see the note in
   `docs/SESSION_LOG.md`).

Skills load at session start, so they became available from the **next** session,
not the one that installed them.

---

## ADR-012 — Error tracking: Sentry
**Date:** 2026-11-05 · **Status:** accepted

**Context.** Phase 0 requires an error-tracking choice. The app holds intimate
personal data, so the default telemetry posture matters more than usual.

**Decision.** `sentry/sentry-laravel` on the API. **`send_default_pii` is off**
and the DSN is empty locally (the SDK then does nothing). Mobile Sentry is
deferred to Phase 10 so it can be set up alongside EAS builds.

**Consequence.** Breadcrumbs and request bodies need scrubbing rules before
production — a stack trace from this app could contain a moment's text. Listed
in the Phase 10 security review.

---

## ADR-011 — Store rules must be re-checked before launch
**Date:** 2026-11-05 · **Status:** accepted, with a standing action

**Context.** Apple and Google rules on what must use in-app purchase versus an
external rail have changed repeatedly, including during litigation. Our pricing
model depends on the split: digital via IAP/RevenueCat, physical and real-world
services via Stripe.

**Decision.** Implement the conservative reading — **digital always IAP, Stripe
only for physical goods and real-world services** — and treat the rules as
volatile. Re-check immediately before launch and before each add-on ships.

**Consequence.** A standing task in Phase 10 and Phase 11. If the rules relax,
we may be leaving margin on the table; that is the right trade against rejection.

---

## ADR-010 — No remote push notifications
**Date:** 2026-11-05 · **Status:** accepted (founder constraint)

**Context.** A reminder product normally wants push. The brief rules it out.

**Decision.** On-device **local** notifications via `expo-notifications`, plus
**email via Resend** for anything that must arrive when the app has not synced.
No APNs, no FCM, no device tokens stored anywhere.

**Consequence.** Reminders depend on the app syncing to schedule them, so
`GET /api/reminders/schedule` returns a **complete desired state** and the app
re-schedules idempotently — self-healing after a missed sync. iOS caps pending
local notifications at 64, so the app schedules the nearest 60 and re-fills.
Upside: there is no push token to leak, and the privacy story is simpler.

---

## ADR-009 — Light modular monolith, no modules package
**Date:** 2026-11-05 · **Status:** accepted

**Context.** 21 modules need structure without ceremony. Packages like
`nwidart/laravel-modules` bring their own autoloading, generators and conventions.

**Decision.** Plain folders under `app/Modules/<Module>/`, autoloaded by the
existing `App\` PSR-4 mapping. A single `ModuleServiceProvider` discovers each
`routes.php` by glob (sorted, for deterministic registration). No third-party
package.

**Consequence.** No generators — a new module is a folder and a `module.md`.
Everything else is stock Laravel, which is the point: a developer who knows
Laravel knows this. If cross-module dependencies get tangled later, reconsider.

---

## ADR-008 — Tests on SQLite by default, MySQL in CI
**Date:** 2026-11-05 · **Status:** accepted

**Context.** Production and dev are MySQL 8. Running the suite on MySQL locally
needs credentials and a created database; SQLite in-memory needs nothing and is
much faster. Engine differences (JSON columns, fulltext, collation, strict mode)
are real and will bite us.

**Decision.** **Both.** `phpunit.xml` defaults to SQLite in-memory so
`composer test` works on a fresh clone with no setup. CI runs the same suite a
**second time against MySQL 8** (job `test-mysql`) so engine-specific breakage
is caught before merge.

**Consequence.** One extra CI job, and two possible answers to "do the tests
pass". The MySQL job is the authoritative one for anything touching schema.

---

## ADR-007 — `laravel/ai` over the Anthropic PHP SDK
**Date:** 2026-11-05 · **Status:** accepted

**Context.** Two credible options for the server-side AI layer:
`laravel/ai` (the official Laravel AI SDK, v1.3.0 stable at time of writing) and
`anthropic-ai/sdk` (the official Anthropic PHP SDK, v1.5.0).

**Decision.** **`laravel/ai` ^1.3.** It is a stable 1.x, it is built for Laravel
(config, queues, testing fakes), and it covers what we need: Anthropic as a
provider, structured output, and a fake gateway for tests — which matters a lot
given every AI call must be queued and no test may make a real call.

**Consequence.** We depend on a Laravel abstraction over the provider, so a
brand-new Anthropic API feature may land there later than in the first-party SDK.
Acceptable: our usage is structured text generation, not the API frontier. The
`Ai` module wraps it behind our own `AiService`, so swapping to the Anthropic SDK
would be one adapter, not a rewrite. **Re-evaluate during P2.T01**, including
whether `laravel/ai`'s own fake replaces our hand-rolled fake driver.

---

## ADR-006 — Model choice: Opus 5.5 strong, Haiku 5.5 light
**Date:** 2026-11-05 · **Status:** accepted

**Context.** Date plans and recaps are the product's visible quality. Safety
pre-screens and tagging are high-volume and low-judgement. Per 1M tokens
(input/output): Opus 5.5 $4/$20, Sonnet 5.5 $2/$10, Haiku 5.5 $0.10/$0.50.

**Decision.** `config('ai.models')` → `strong: claude-opus-5-5`,
`light: claude-haiku-5-5`. Per-task model **and effort** in
`config('ai.tasks')`. No model id anywhere in code.

**Consequence.** Opus on date plans is the main cost driver, which is why
per-couple caps and the cost guard exist. **Sonnet 5.5 is the documented
step-down** if cost per couple proves too high — a one-line config change, and
the reason the mapping is config rather than code.

---

## ADR-005 — Reveal gates are enforced server-side
**Date:** 2026-11-05 · **Status:** accepted

**Context.** Several features hide one partner's input until both have
contributed: date questionnaire, Daily Spark, Date Debrief, and (permanently)
check-in answers, gift vault and locked Love Notes.

**Decision.** The API **does not return** the withheld data. Hiding it in the UI
is explicitly not sufficient. Each module's tests assert the field is **absent
from the JSON**, not merely unrendered.

**Consequence.** Slightly more endpoint shaping — a resource's output depends on
viewer and state. Worth it: this is the product's core promise, and a client-side
gate would be one `console.log` away from breaking it.

---

## ADR-004 — Palette adjusted to meet WCAG AA
**Date:** 2026-11-05 · **Status:** accepted

**Context.** The founder's twelve brand colours were checked against WCAG 2.1
before implementation. Twelve text pairs failed AA in light mode and eleven
on-fill pairs failed in dark mode. Worst cases: Lavender as text on white
(2.09:1), white on Rosé (3.00:1), and Cherry `#E5384F`, where **neither** a
white label (4.19:1) **nor** an ink label (4.19:1) reached 4.5:1 — a fill with
no accessible label at all.

**Decision.** Keep all twelve brand colours **as fills, surfaces and ink**, and
add two derived token families rather than restyling the brand:

* **`*Text`** — the same hue darkened enough to be read as text
  (`primaryText` `#E4002C`, `secondaryText` `#BC5100`, `successText` `#347F5C`,
  `dangerText` `#DE1D37`, `aiText` `#7450FF`).
* **`on*`** — the label that sits on a fill. Plum in almost every case; white
  only on light-mode Cherry. In dark mode **every** `on*` is plum, because the
  dark fills are light.

Two genuine changes to the spec:

1. **Cherry darkened `#E5384F` → `#E2243D`** (light theme fill only), so a white
   label reaches 4.61:1. There was no label colour that worked at the original
   value.
2. **Petal `border` documented as decorative** (1.26:1 on white). WCAG 1.4.11
   exempts purely decorative boundaries, so Petal stays for card edges and
   dividers, and a new **`borderStrong`** (`#C17990` light, `#845DA9` dark)
   serves inputs, toggles and anything interactive at 3:1. A separate **`focus`**
   token was added because Rosé itself is only 2.85:1 on cream.

Derived values were solved to a **4.6:1 / 3.1:1** target so rounding never drops
a pair under the line.

**Consequence.** More tokens, and a rule developers must learn: fills use brand
tokens, words on fills use `on*`, coloured words use `*Text`. In exchange the
palette is verifiably AA in both themes, and
`mobile/src/theme/__tests__/contrast.test.ts` fails CI on any regression —
including a case asserting the original Cherry still fails, so the gate cannot
rot into a no-op.

---

## ADR-003 — Lint rule uses `no-restricted-syntax`, not a custom plugin
**Date:** 2026-11-05 · **Status:** accepted

**Context.** The brief requires an ESLint rule banning raw colours and font
names outside the theme. A named custom rule (`swoon/no-raw-design-values`)
would need a local plugin package and a build step.

**Decision.** Four `no-restricted-syntax` selectors in `mobile/eslint.config.js`,
each with a message naming the token to use instead. `src/theme/**` and test
files are exempt.

**Consequence.** No new dependency, no plugin to maintain. The rule name in
violations is `no-restricted-syntax` rather than something branded — the message
carries the meaning. Two documented exceptions exist in `app.config.ts`, where
native manifest values are baked in at build time and cannot read the
TypeScript theme.

---

## ADR-002 — API-only Laravel: Vite and Blade removed
**Date:** 2026-11-05 · **Status:** accepted

**Context.** `laravel/laravel` ships a Vite + Blade front end. This application
is JSON-only, consumed by the Expo app.

**Decision.** Removed `api/package.json`, `vite.config.js`, `resources/css`,
`resources/js` and the welcome view. `routes/web.php` keeps only what a browser
must reach. `composer dev` runs **queue worker + scheduler + log tail** — Herd
serves HTTP itself, and does not run the other two for you.

**Consequence.** No `npm` step in the API at all, and a `composer dev` that
supplies exactly what Herd does not. If a web surface is ever needed (invite
landing pages, OAuth callbacks), it returns to `routes/web.php` — adding Blade
back is a small, deliberate step.

---

## ADR-001 — "Swoon" is a placeholder in exactly two config values
**Date:** 2026-11-05 · **Status:** accepted

**Context.** The working name will probably change. The repository directory is
`Smitten`, which is already a different name from the product — proof the problem
is real.

**Decision.** The name lives in `api/config/swoon.php → 'name'` (via
`SWOON_NAME`) and `mobile/src/config/app.ts → APP_NAME` (via
`app.config.ts`/`EXPO_PUBLIC_APP_NAME`). Nothing else hard-codes it; emails,
AI prompts and store copy read it from config.

**Consequence.** Renaming is two values plus assets. The `swoon` deep-link
scheme, the `test.swoon.app` bundle identifiers and the `config/swoon.php`
filename **do** contain the name and would need a coordinated change — bundle
identifiers in particular cannot be changed after store release, so **pick the
final name before the first TestFlight build** (flagged in Phase 10).

---

## Template

```markdown
## ADR-0NN — <short title>
**Date:** YYYY-MM-DD · **Status:** accepted | superseded by ADR-0NN | open

**Context.** What forced a choice.

**Decision.** What we chose.

**Consequence.** What this costs us, and what to watch.
```
