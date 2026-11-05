# Session log — Swoon

One short entry per working session. **Newest first.**

Format: date · task ID(s) · what changed · anything left over.

---

## 2026-11-05 — P0.T10 · MySQL connected, `users` schema live

`DB_PASSWORD` had been filled in, so the one blocker on this task was gone.

**Built**

* `swoon` and `swoon_test` created (utf8mb4 / utf8mb4_unicode_ci); `swoon` was
  already carrying the default tables, so it was altered to the project
  collation rather than recreated.
* The seven profile columns later phases assume — `timezone` (default
  `Europe/London`), `locale` (`en-GB`), `colour`, `avatar_path`,
  `dietary_needs`, `accessibility_needs`, `date_of_birth` — went into the
  **base** `create_users_table` migration, not a second one. Nothing is
  deployed, so a clean 15-column schema beats an immediate `ALTER`.
* `HasApiTokens` on `App\Models\User` (the Sanctum installer asked for it in
  P0.T01 and it was still missing), plus fillable and the json/date casts.
* `api/tests/Feature/Accounts/UserTest.php` — token issuance, the UK defaults,
  and a cast round-trip.

**Verified** `migrate:fresh` twice against MySQL 8.0.46. The suite runs green on
SQLite *and* on MySQL 8 — the second run is the one that matters for schema
(ADR-008), and `docs/DEV_TOOLING.md` § 2 now says how to do it locally.
`composer check` clean: Pint 52 files, Larastan 6 no errors, 6 tests.

**One thing worth knowing.** Larastan could not read the cast types off the
schema and reported `date_of_birth` as `string`. The choice was a suppression or
telling it the truth, so `User` now carries three `@property` lines. Expect the
same for every model with a non-trivial cast.

**Left over** `colour` is a plain `varchar(16)`, not an enum — the four valid
values are validated in the Form Request in P1, which keeps the column from
needing an `ALTER` when a colour is added. `email` and `password` are still
non-nullable; P1.T01 relaxes them for Apple private relay and social-only
accounts.

**Next:** **P0.T11** — Resend transport. The `MAIL_MAILER=log` half can be done
now; the live send needs `RESEND_API_KEY` and a verified domain.

---

## 2026-11-05 — P0.T01 … P0.T09 · Project setup

**Kickoff session.** Set up the monorepo, tooling, brand, documentation system
and the master build plan. **No feature code** — that starts at Phase 1.

**Built**

* **`api/`** — Laravel 13.11 on PHP 8.5.11, API-only (Vite/Blade removed).
  Sanctum, Pest 4, Pint, Larastan 6, `laravel/ai` ^1.3, Resend, S3 flysystem,
  Sentry. 21 module folders under `app/Modules/` with a `ModuleServiceProvider`
  that discovers each `routes.php`. `config/swoon.php`, `config/ai.php`,
  `config/pricing.php`. Health endpoint + 3 tests.
* **`mobile/`** — Expo 58, RN 0.88, React 19.3, TS 6 strict, Expo Router,
  TanStack Query, Fraunces + Nunito, Phosphor. Typed theme (light + dark),
  component library v1 (Button, Card, Chip, AvatarPair, EmptyState, Sheet,
  ProgressBar, AITag), typed API client, placeholder Home screen that calls
  the health endpoint.
* **Docs** — CLAUDE.md, README.md, and `docs/`: PLAN (107 tasks across 12
  phases), ARCHITECTURE (ERD + AI pipeline in Mermaid), BRAND, UX_RULES,
  PRICING, AI_PROMPTS, MODULES, DECISIONS (14 ADRs), DEV_TOOLING, this log,
  and `templates/module.md`. 21 canonical + 20 mobile `module.md` files.
* **Tooling** — module-docs Stop hook, pre-commit (Pint + ESLint on staged
  files only, plus a `.env` guard), `.gitignore`, GitHub Actions CI (api,
  test-mysql, mobile, docs), Graphify graph.

**Verified, not assumed**

* `GET /api/health` returns 200 over real HTTP:
  `{"status":"ok","app":"Swoon",…}`.
* API: 3 Pest tests pass · Larastan level 6 clean · Pint clean.
* Mobile: 57 Jest tests pass · `tsc --noEmit` clean · ESLint clean · Prettier clean.
* The ESLint ban on raw design values **works** — it caught two hex literals in
  `app.config.ts`, which now carry documented exceptions (native manifest values
  cannot read the TypeScript theme).

**The palette did not pass as specified.** Checked all 12 brand colours against
WCAG 2.1 before implementing: **12 text pairs failed AA in light mode and 11
on-fill pairs in dark mode.** Worst case was Cherry `#E5384F`, where *neither* a
white nor an ink label reached 4.5:1 — a fill with no accessible label at all.

Resolution (ADR-004): keep all 12 brand colours as **fills**, and add `*Text`
and `on*` token families for text and knockout labels, plus `borderStrong` and
`focus`. Two real changes to the spec — Cherry darkened to `#E2243D`, and Petal
documented as a decorative divider with `borderStrong` added for controls.
Zero failing pairs now, gated by `contrast.test.ts`, which also asserts the
**pre-fix** Cherry still fails so the gate cannot rot into a no-op.

**Decisions** ADR-001 … ADR-014. Notably: `laravel/ai` over the Anthropic PHP
SDK (ADR-007); Opus 5.5 strong / Haiku 5.5 light with Sonnet 5.5 as the
documented step-down (ADR-006); tests on SQLite by default with a MySQL 8 CI job
(ADR-008); light modular monolith, no modules package (ADR-009).

**Left over / needs the founder**

1. **`.claude/settings.json` was not written** — the Stop-hook registration was
   blocked as a self-modification. The hook script exists and passes a dry run;
   the JSON needs creating by hand (content given in the session summary).
2. **`DB_PASSWORD`** — MySQL root on this machine has a password, so migrations
   have not been run. This is **P0.T10**, the next task.
3. ~~Agent Skills plugin is not installed.~~ **Resolved later the same session**
   on the founder's go-ahead: installed
   `agent-skills@addy-agent-skills` v0.6.15 as a **plugin** at user scope
   (`claude plugin install`), not vendored into `.claude/skills/` — see ADR-013
   for why. Both requested plugins are now active. Still worth doing: declare
   the marketplace in the project's `.claude/settings.json` under
   `extraKnownMarketplaces` so teammates get it on clone.
4. **Keys by hand:** `ANTHROPIC_API_KEY`, `RESEND_API_KEY` (+ domain
   verification), RevenueCat, Stripe, S3, Apple/Google sign-in.
5. **Herd desktop was not running**, so `herd link` / `herd share` were not
   exercised. The API was verified with `php artisan serve` instead.
6. **Graphify CLI 0.9.97 vs bundled skill 0.9.102** — a version mismatch warning;
   upgrade the package rather than running `graphify install`.

**Next:** **P0.T10** — connect MySQL and run the first migration (add
`HasApiTokens` to `User` and the `users` columns later phases assume).
