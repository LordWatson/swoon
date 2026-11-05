# Decisions — Swoon

ADR-style log. **Newest first.** One entry per non-obvious decision.

Add an entry whenever you choose between real alternatives, deviate from a spec,
add a dependency, or make a call a future session would otherwise re-litigate.
Keep each entry short: context, decision, consequence.

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

## ADR-013 — Agent Skills plugin is not installed
**Date:** 2026-11-05 · **Status:** open — needs founder decision

**Context.** The brief asks that Ponytail and Agent Skills both be active. Only
`ponytail@ponytail` (v5.3.3) is installed. The `addy-agent-skills` marketplace
is registered in `~/.claude/settings.json` but no plugin from it is enabled.

**Decision.** Reported rather than worked around, per the brief. Not installed —
the fallback (`npx skills add <repo> --agent claude-code` into project-level
`.claude/skills/`) needs explicit approval first.

**Consequence.** The spec → plan → build → test → review lifecycle in the
session protocol is a convention to follow by hand until the plugin is enabled.
Nothing is blocked.

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
