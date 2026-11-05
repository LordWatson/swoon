# CLAUDE.md — Swoon

**Swoon** is a relationship concierge app for couples: two people link into a
shared space, and everything — dates, moments, plans, challenges — belongs to the
couple, with some things private to one partner.

*"Swoon" is a placeholder, living only in `api/config/swoon.php → 'name'` and
`mobile/src/config/app.ts → APP_NAME`.*

## Session protocol (short — full version in `docs/PLAN.md`)

1. Read `CLAUDE.md`, `docs/PLAN.md`, `docs/MODULES.md`, the last 3
   `docs/SESSION_LOG.md` entries, and the `module.md` of anything you'll touch.
2. Take the **first unchecked task** in the current phase. State its ID and
   acceptance criteria before writing code.
3. Build with tests. Run the linters and the relevant suite before "done".
4. Update `module.md` + `MODULES.md` (+ `AI_PROMPTS.md` / `DECISIONS.md` if
   relevant), tick the task, move **Current focus**, add a `SESSION_LOG.md` entry.
5. Refresh the graph after structural changes.
6. One focused commit per task: `feat(moments): add offline sync [P4.T06]`.

## Stack and commands

Laravel 13 · PHP 8.5 · MySQL 8 · Sanctum tokens · JSON-only API.
Expo · Expo Router · TypeScript strict. Claude via `laravel/ai`, **server-side
only**. Resend email, **local notifications only — no remote push**. RevenueCat
for digital, Stripe for physical only.

```powershell
cd api     && composer dev     # queue:work + schedule:work + pail
           && composer check   # pint --test + larastan + pest
cd mobile  && npm start
           && npm run check    # tsc + eslint + prettier + jest
```

Herd serves `https://swoon-api.test` but runs **neither** the queue nor the
scheduler. **Phones and emulators cannot resolve `.test`** — Android emulator
`http://10.0.2.2:8000`, device `herd share` or the LAN IP
(`docs/DEV_TOOLING.md` § 4).

## Documentation rule

**Every module has a `module.md`, updated in the same change as the code.** A
task is not done until the docs match.

* Canonical: `api/app/Modules/<Module>/module.md` (full stack).
* Mobile detail: `mobile/src/features/<module>/module.md`, linking to it.
* Template: `docs/templates/module.md` — 12 required headings.
* Then update `docs/MODULES.md` and add a `SESSION_LOG.md` line.
* New/changed AI prompt → **bump the version** + `docs/AI_PROMPTS.md`.
* Non-obvious decision → `docs/DECISIONS.md`.

The Stop hook warns on missed docs; CI's `docs` job is the actual gate.

## Brand and UX

Follow **`docs/BRAND.md`** and **`docs/UX_RULES.md`** — they are the review
checklist.

* **Theme tokens always.** No raw hex, `rgb()` or font names outside
  `mobile/src/theme/` (ESLint enforces it).
* Fills use brand tokens · words **on** a fill use `on*` · coloured words use
  `*Text`. Several brand colours cannot carry text at AA, which is why these
  exist.
* **Reuse `mobile/src/components/`.** A new component needs a reason, a doc and a
  test. A one-off styled `View` in a feature folder is a bug.
* The palette is AA-gated by `mobile/src/theme/__tests__/contrast.test.ts`, which
  **fails CI**. A new token needs a BRAND.md row and a case there.
* Voice: second person, "you two", short sentences, **British spelling**, one
  emoji per sentence at the end. Celebrate, never scold.

## AI rules

* **Server-side only.** The key lives in `api/.env` and never ships in the app.
  No endpoint forwards a prompt.
* **`AiService` is the only door**, and **every call is a queued job** — never in
  an HTTP request.
* **Prompts are versioned files** pinned in `config('ai.prompts.active')`. Never
  edit a released version in place; `ai_generations` references it.
* **Structured output validated** before anyone sees it.
* **SafetyGuard on every user-facing generation** — a separate, tested step that
  **fails closed**. On a concern: discard the output, return only the UK
  signpost, and never reveal one partner's concern to the other.
* **Private data never enters a prompt.** The filter is an **allow-list per
  task**, so a new private column is excluded by default.
* **Cost logged on every call**, failures included. Caps checked **before**
  dispatch. Model ids from `config/ai.php`, never a literal.
* AI output is always labelled (Lavender ✨), regenerable, ratable, and never
  presented as a partner's words.
* Tests use `AI_DRIVER=fake`. **A test that makes a real AI call is a failure.**

## Privacy and safety (non-negotiable)

* **No location tracking of a partner, ever.** No "where is my partner", no
  history, no coordinates. The planner stores the postcode **outcode only**;
  photo EXIF including GPS is stripped on upload. This app must not become a tool
  for coercive control.
* **Private means private.** Gift vault, individual check-in answers, locked love
  notes, private moments and private love-map entries are never visible to the
  partner and never reach shared AI output.
* **Reveal gates are server-side** — withheld data is *absent from the JSON*, not
  merely unrendered. Default to private when ambiguous.
* **AI is a playful assistant, not a therapist.** Insights observe and suggest;
  they never diagnose, judge, or compare the partners.
* **Unlink and delete are immediate.** Unlinking needs no consent from the other
  partner — requiring it would let a controlling partner trap someone. Deletion
  cascades; export works (UK GDPR).
* **Every privacy rule has a test asserting absence from the response.**

## Coding conventions

**Laravel** — `declare(strict_types=1)` everywhere. **Form Requests** for
validation, **API Resources** for every response, **a Policy on every model**,
**Actions** for business logic; controllers only authorise, validate, call an
Action, return a Resource. Modules are plain folders under `app/Modules/`, routes
discovered by `ModuleServiceProvider`. Money is **integer pence**; timestamps UTC
in the DB, displayed and scheduled in the **user's** timezone. Larastan level 6
stays clean — add a scoped `ignoreErrors` with a comment rather than lowering it.

**Mobile** — Expo Router; **TanStack Query** for server state; Zustand only where
client-only state must outlive a screen. `src/api/client.ts` is the only thing
that calls `fetch`. No inline styles outside theme helpers, no magic numbers.
Anything creatable offline uses a **client ULID** so a retried sync cannot
duplicate. Tokens in `expo-secure-store`. 44 pt targets, a label on every icon
button, dynamic type respected.

## Minimalism

**Ponytail is active.** The best code is the code never written. Reuse before
writing; prefer framework and native features; no speculative abstraction or
config nobody asked for. **No new dependency without a `docs/DECISIONS.md`
line.** A shortcut with a known limit gets `shortcut: <limit>, <when to
upgrade>`. Never cut validation at trust boundaries, error handling that prevents
data loss, security, accessibility, or the privacy guarantees above.

## OmniRoute

**Local dev gateway only**, not installed here. The app's runtime calls Anthropic
directly. **Never route user data from the app through OmniRoute** — it would put
intimate personal data through a dev-grade hop and break the single-door AI
guarantee (`docs/DEV_TOOLING.md` § 9).

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- When graphify-out/graph.json exists, run `graphify query "<question>"` before your first grep, search or source-file read for a codebase question, and put this rule in every subagent prompt that explores code. Read raw files only for lines the graph or an earlier read already located. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
- A committed graphify-out/graph.json is rewritten whole on every build, so a large diff to it in a pull request is expected. Do not flag it as churn or suggest skipping the graph rebuild or its hooks.
