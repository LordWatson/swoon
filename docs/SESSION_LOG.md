# Session log — Swoon

One short entry per working session. **Newest first.**

Format: date · task ID(s) · what changed · anything left over.

---

## 2026-11-05 — Review and audit fixes (no task ID)

A code review of P0.T11 and a whole-repo audit, then every finding fixed. No
feature work. Two findings were serious enough to be worth the detour.

**The two that mattered**

1. **CI had never run.** `.github/workflows/ci.yml` triggered on `push` to
   `main`; this repo's branch is `master`. Only pull requests ran anything, so
   both of today's commits went in with every gate skipped — the contrast gate,
   the docs gate, the MySQL job, all of it. Now `[master, main]`.
2. **The API client reported a captive portal as a healthy connection.**
   `apiRequest` fell back to `{ message: <body> }` for any body that would not
   parse as JSON, including on a **200**, and handed that back as the expected
   type. A café portal answering 200 with an HTML page made Home render
   `Connected to  ✅` in green. It now throws on a non-JSON success and keeps
   the text only as an error message.

**Also fixed**

* `config/ai.php` held provider prices as USD floats next to caps in pence with
  no exchange rate anywhere — the first cost guard would have been ~100× wrong,
  in money. Now `pricing_usd_cents_per_mtok` in whole numbers plus one
  `usd_to_pence` rate (ADR-016). The `safety.enabled` flag is gone: a
  documented fail-closed guarantee should not be one env var from off
  (ADR-017). Together and Forever caps were identical copies; defined once now.
* **`APP_NAME="${SWOON_NAME}"` was a trap I introduced in P0.T11.** Verified
  against the project's own Dotenv: substitution only works for a variable
  defined *earlier in the same file*, and otherwise leaves the literal text
  `${SWOON_NAME}`. A production env assembled in another order would have put
  that string in the header of a real invite email. `AppServiceProvider::boot()`
  now maps `app.name` and `mail.from.name` from `config('swoon.name')`, which
  also covers Laravel's own notifications — they never passed through
  `SwoonMailable`, so that class only ever protected half the mail (ADR-015).
  `SwoonMailable`'s constructor is deleted as a result.
* `resend.routes` is `false` in `AppServiceProvider::register()` rather than in
  an env line a hand-built deploy can omit, with a test asserting the
  unsigned webhook route stays unregistered.
* **The API client had no tests at all** — the one file in the app that touches
  the network. Nine now: the bearer header, 204, Laravel's 422 shape, a
  transport failure as status 0, 401, the non-JSON success above, a non-JSON
  error body, cancellation, and the timeout.
* An aborted request was relabelled `ApiError(0, 'Network request failed')`, so
  a cancelled query showed the offline state and got retried. It is rethrown.
* **Nothing ever timed out.** `fetch` has no default timeout, so a connection
  that neither answers nor fails left the promise unsettled — no error, no
  retry, no offline state, just a spinner. 15s via `AbortController`, not
  `AbortSignal.timeout`/`any`, which React Native does not polyfill and which
  would therefore have passed in Jest and thrown on a device.
* `ModuleServiceProvider` re-read and re-registered all 21 module route files
  on every request even with a warm route cache. One `routesAreCached()` guard,
  matching what Laravel's own route provider does.
* **The Stop hook `CLAUDE.md` promises was never registered** — the script was
  committed and nothing ran it. Registered, and taught to see untracked files:
  it used only `git diff`, so a brand-new Action file, which is most of what
  Phase 1 writes, was invisible until staged.
* `.claude/settings.json` is committed and hardcoded an absolute path to one
  machine's Python install, which would fail on every tool call for anyone
  else. `graphify` resolves on PATH; verified.
* An eslint comment claimed icon-button labels were linted. No such rule
  existed, so UX rule 12 was enforced by nothing — the comment is gone rather
  than a dependency added. All eight components do label correctly; only the
  claimed enforcement was imaginary.
* **147 `.gitkeep` files and 147 empty directories deleted** — seven empty
  folders each for 21 modules, 20 of which have no code. Folders appear when
  the first file lands.
* Small ones: unused `darkTheme` export; a commented-out line in the seeder;
  `useSystemTheme()` rebuilt the theme object on every render, which is the
  context value, so every themed component re-rendered — now memoised.

**Verified** `composer check` clean (Pint, Larastan 6, 12 tests / 32
assertions); the API suite green on SQLite **and** MySQL 8; `npm run check`
clean (tsc, ESLint, Prettier, 66 tests). The docs hook exercised both ways.

**Worth knowing.** `MAIL_FROM_ADDRESS` in `.env` is now `swoon@lordwatson.uk`,
not the unverifiable `hello@swoon.test` — so the live Resend send from P0.T11
very likely did arrive. `.env.example` still ships the placeholder.

**Next:** **P0.T12** — queue, scheduler and the Maestro smoke harness. Last
task in Phase 0.

---

## 2026-11-05 — P0.T11 · Resend transport and the first real email

**Built**

* `App\Mail\SwoonMailable` — the base every Swoon email extends. It exists for
  one guarantee: the from-name comes from `config('swoon.name')`, not
  `MAIL_FROM_NAME`, which interpolates `${SWOON_NAME}` and would quietly empty
  on a deploy that forgot it.
* `App\Mail\TestPingMail` + `resources/views/mail/ping.blade.php` — a markdown
  mailable on Laravel's own mail theme. No email CSS written, no vendor views
  published.
* `artisan swoon:mail-test {email}` — validates the address, sends, and with
  the `resend` mailer prints the Resend email id.
* Four tests: the product name reaches the subject and body from config, the
  from-name survives an empty `MAIL_FROM_NAME`, the command sends to the address
  given, and a malformed address sends nothing.

**Step 1 needed no code.** The `resend` mailer in `config/mail.php` and
`services.resend.key` were both already there from P0.T01.

**Two things changed outside the task's steps.**

1. Laravel's mail theme hardcodes `config('app.name')` in the email header and
   footer, which would have left a renamed product stale in every email. So
   `APP_NAME="${SWOON_NAME}"` now, and the Product block moved above the Laravel
   block in `.env` / `.env.example` — Dotenv's `${}` only reads backwards. The
   product is named in one env var and one config file, as `CLAUDE.md` says.
2. `RESEND_ROUTES=false`. The package registers `POST /resend/webhook`, and its
   controller attaches the signature middleware **only** when
   `RESEND_WEBHOOK_SECRET` is set — with no secret it fails open and accepts
   anything from anyone. Nothing here handles Resend webhooks yet, so the route
   is off until bounce handling is built, and then with a secret. Route count
   is back to 6.

**Verified**

* `MAIL_MAILER=log`: `From: Swoon <hello@swoon.test>`, subject
  `Swoon is plugged in`, header and footer both branded, four sentences each
  ending in one emoji, British throughout.
* `MAIL_MAILER=resend`: accepted, email id
  `01a1abb8-d23b-7919-bbc8-9def8dc6797d`. The transport throws unless Resend
  returns a non-empty id, so that is a real 2xx.
* `composer check` clean: Pint, Larastan 6 no errors, 10 tests / 29 assertions.

**Needs the founder.** The Resend key is **send-only**, so the account's
verified domains could not be listed from here. Resend accepted a send from
`hello@swoon.test`, which is not a verifiable domain — check the dashboard and
the inbox to see whether it was delivered or failed after acceptance. If it
failed, a real sending domain needs verifying and `MAIL_FROM_ADDRESS` pointing
at it; the code does not change either way.

`Mail::fake()` in tests, plus `MAIL_MAILER=array` in `phpunit.xml`, so the suite
sends nothing.

**Next:** **P0.T12** — queue, scheduler and the Maestro smoke harness. Last task
in Phase 0.

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
