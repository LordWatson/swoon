# Build plan — Swoon 💞

**Last updated:** 2026-11-05
**Current focus:** **P2.T03** — structured output validation. **P2.T01–T02 are done**: one interface to the model, a fake the suite runs on by default, and prompts as pinned, versioned files.

---

## How to use this plan

This file is fed to Claude at the start of every session with the word
"continue". It must be self-sufficient — everything needed to pick up work is
here or linked from here.

### Session protocol

**At the start of every session:**

1. **Read, in this order:** `CLAUDE.md` · `docs/PLAN.md` (this file) ·
   `docs/MODULES.md` · the **last 3 entries** of `docs/SESSION_LOG.md` · the
   `module.md` of any module you will touch.
   Use the Graphify graph (`graphify-out/GRAPH_REPORT.md`) to orient **before
   grepping** — it is faster and gives you the shape of things.
2. **Pick the first unchecked task** in the current phase, unless a task is
   named for you. **State which task you are doing and its acceptance
   criteria** before writing code.
3. Use the **spec → plan → build → test → review** lifecycle where it helps.
   Keep **Ponytail's minimalism**: reuse what exists, prefer framework and
   native features, no speculative abstractions. The best code is the code
   never written.
4. **Build with tests.** Run the full relevant suite and the linters before
   calling anything done.
5. **Close the loop:**
   - update the module's `module.md` (**same change as the code**),
   - update `docs/MODULES.md` (index + status),
   - update `docs/AI_PROMPTS.md` and/or `docs/DECISIONS.md` if relevant,
   - tick the task here and move **Current focus**,
   - add an entry to `docs/SESSION_LOG.md`.
6. **Refresh the Graphify graph** (`/graphify . --update`) after structural
   changes.
7. **One focused git commit per task**, conventional message referencing the
   task ID — e.g. `feat(accounts): add partner invite flow [P1.T04]`.

### Rules for this plan

* **One task = one focused session.** If a task starts sprawling, split it and
  record the split here.
* **Size L must be split.** An L task that reaches implementation unsplit is a
  planning failure — break it into S/M children first.
* **Order favours a runnable app.** Each phase should leave the app working
  end-to-end, not half-wired.
* Don't skip a task's **docs to update** line. A task is not done until the docs
  match the code.
* If reality diverges from the plan, **change the plan** in the same commit and
  say so in the session log.

### Status key

`[ ]` not started · `[~]` in progress · `[x]` done · `[-]` dropped (say why)

---

## Phase status

| Phase | Goal | Tasks | Status |
|---|---|---|---|
| **0** | [Foundations](#phase-0--foundations) | 12 | **12 / 12 done** ✅ |
| **1** | [Accounts & Couples](#phase-1--accounts--couples) | 10 | **10 / 10 done** ✅ |
| **2** | [AI platform](#phase-2--ai-platform) | 9 | 0 / 9 |
| **3** | [AI Date Planner](#phase-3--ai-date-planner) | 9 | 0 / 9 |
| **4** | [Moments & On This Day](#phase-4--moments--on-this-day) | 9 | 0 / 9 |
| **5** | [Planner & Milestones](#phase-5--planner--milestones) | 6 | 0 / 6 |
| **6** | [Engagement](#phase-6--engagement) | 9 | 0 / 9 |
| **7** | [AI Recap & Insights](#phase-7--ai-recap--insights) | 7 | 0 / 7 |
| **8** | [Monetisation](#phase-8--monetisation) | 8 | 0 / 8 |
| **9** | [More modules](#phase-9--more-modules) | 10 | 0 / 10 |
| **10** | [Launch readiness](#phase-10--launch-readiness) | 11 | 0 / 11 |
| **11** | [High-ticket add-ons](#phase-11--high-ticket-add-ons) | 7 | 0 / 7 |

**107 tasks.** Phases 0–4 are the critical path to something a real couple can
use; 5–7 make it worth paying for; 8 makes it a business; 9–11 complete it.

---

## Phase 0 — Foundations

**Goal.** A monorepo where both halves boot, the app talks to the API, the theme
and component library exist and are AA-verified, and every check runs in CI.

**Exit criteria.**
- [x] `api/` boots; `GET /api/health` returns 200 over HTTP.
- [x] `mobile/` shows a themed Home screen that calls the health endpoint.
- [x] Theme tokens (light + dark) with a **contrast gate that fails CI**.
- [x] Component library v1: Button, Card, Chip, AvatarPair, EmptyState, Sheet, ProgressBar, AITag.
- [x] Pint, Larastan 6, ESLint, Prettier, tsc strict, Pest, Jest all pass.
- [x] CI runs every check.
- [x] All docs written; 21 module docs scaffolded.
- [x] MySQL connected and migrated.
- [x] Resend transport verified end to end.
- [x] Queue worker and scheduler verified.

**Dependencies.** None.

---

### `[x]` P0.T01 — Monorepo and Laravel 13 API · **M**

**Steps**
1. Create `api/` with `composer create-project laravel/laravel api "^13.0"`.
2. `php artisan install:api` (Sanctum + `routes/api.php`).
3. Add `laravel/ai`, `resend/resend-laravel`, `league/flysystem-aws-s3-v3`, `sentry/sentry-laravel`.
4. Add dev deps: Pest 4, `pest-plugin-laravel`, Larastan 3.
5. Strip the Vite/Blade front end (API-only).
6. Add `config/swoon.php`, `config/ai.php`, `config/pricing.php`.
7. Add `ModuleServiceProvider` globbing `app/Modules/*/routes.php`; register it.
8. Add `composer` scripts: `dev`, `test`, `lint`, `lint:check`, `analyse`, `check`.

**Acceptance criteria**
- `php artisan --version` reports Laravel 13.
- `App\Modules\*` autoloads with no extra Composer config.
- `composer dev` runs queue + scheduler (not `serve`, not Vite). *Amended in
  P0.T12: the log tail moved to `composer logs`, because Pail needs `pcntl` and
  so cannot run on Windows — see ADR-018.*

**Docs to update** `docs/ARCHITECTURE.md`, `docs/DECISIONS.md` (ADR-002, ADR-007, ADR-009).

> **Done 2026-11-05.** Laravel 13.11, PHP 8.5.11, `laravel/ai` ^1.3.

---

### `[x]` P0.T02 — Expo app with Expo Router and TS strict · **M**

**Steps**
1. `create-expo-app mobile --template default` (Expo 58, `src/` layout).
2. Remove the template example screens and components.
3. Add deps: TanStack Query, Fraunces + Nunito, Phosphor, `react-native-svg`.
4. Add dev deps: ESLint, Prettier, Jest, `jest-expo`, RNTL.
5. Replace `app.json` with `app.config.ts` (env-driven name and API URL).
6. `tsconfig` strict + `types: [jest, node]`.
7. Add npm scripts: `lint`, `format`, `typecheck`, `test`, `check`.

**Acceptance criteria**
- `npm start` boots; `npm run check` passes.
- `EXPO_PUBLIC_API_URL` overrides the base URL.
- Deep-link scheme `swoon` matches `config/swoon.php`.

**Docs to update** `docs/DEV_TOOLING.md`.

> **Done 2026-11-05.** Expo 58, RN 0.88, React 19.3, TS 6.

---

### `[x]` P0.T03 — Theme tokens and the contrast gate · **M**

**Steps**
1. `colours.ts` (light + dark), `typography.ts`, `shape.ts`, `motion.ts`, `modules.ts`, `index.ts`.
2. Check **every** token pair against WCAG before writing it down.
3. Where a brand colour cannot carry text, add `*Text` and `on*` tokens rather than restyling the brand.
4. Write `__tests__/contrast.test.ts` asserting every pair.
5. Add a case asserting a **known-bad** value still fails, so the gate cannot rot into a no-op.
6. Add the ESLint ban on raw hex/rgb/font names outside `src/theme`.

**Acceptance criteria**
- 0 failing pairs in both themes.
- A raw hex outside the theme is an ESLint **error**.
- `reduceMotion` is read once and exposed via `useMotion()`.

**Docs to update** `docs/BRAND.md`, `docs/DECISIONS.md` (ADR-003, ADR-004).

> **Done 2026-11-05.** 23 pairs checked twice over; Cherry darkened to `#E2243D`
> (no accessible label existed at `#E5384F`); `borderStrong` and `focus` added.
> 57 Jest assertions.

---

### `[x]` P0.T04 — Component library v1 · **M**

**Steps** Build Button, Card, Chip, AvatarPair, EmptyState, Sheet, ProgressBar,
AITag. Export from `src/components/index.ts`.

**Acceptance criteria**
- Every component reads tokens via `useTheme()` — no raw values.
- 44 pt minimum targets; a label on every control.
- `EmptyState` **requires** emoji + message + action (UX rule 7 by construction).
- `AvatarPair` never relies on colour alone.
- `AITag` uses `onAi`, so it is readable in both themes.

**Docs to update** `docs/UX_RULES.md` (rule 15 reference).

> **Done 2026-11-05.**

---

### `[x]` P0.T05 — Linting and static analysis · **S**

**Steps** `pint.json` (strict types, strict comparison, ordered imports);
`phpstan.neon` at level 6 over app/config/database/routes/tests; ESLint flat
config + Prettier.

**Acceptance criteria** `composer check` and `npm run check` both pass clean.

**Docs to update** `docs/DEV_TOOLING.md`.

> **Done 2026-11-05.** Larastan level 6 clean; Pint fixed 25 files. Two scoped
> `ignoreErrors` for Pest's `$this` rebinding, confined to `tests/*`.

---

### `[x]` P0.T06 — Health endpoint and typed API client · **S**

**Steps** `GET /api/health` (unauthenticated, no DB); `src/api/client.ts` with
`ApiError` carrying status, `isValidation` / `isUnauthenticated` / `isRetryable`;
injectable token reader; `fetchHealth()`.

**Acceptance criteria**
- Health returns 200 with no DB connection.
- A transport failure is **status 0**, distinct from a 5xx (offline handling needs that).
- A non-JSON body does not crash the parse.
- Health leaks no configuration (asserted).

**Docs to update** `docs/ARCHITECTURE.md` § 2.

> **Done 2026-11-05.** Verified over real HTTP: `{"status":"ok","app":"Swoon",…}`.

---

### `[x]` P0.T07 — Documentation system · **M**

**Steps** `docs/templates/module.md`; `CLAUDE.md`; `README.md`; BRAND, UX_RULES,
PRICING, ARCHITECTURE (ERD + AI pipeline in Mermaid), AI_PROMPTS, MODULES,
DECISIONS, SESSION_LOG, DEV_TOOLING; 21 canonical `module.md` + 20 mobile ones.

**Acceptance criteria**
- Every module has a canonical doc with **all 12 required headings**.
- Every mobile doc links to its canonical doc.
- `MODULES.md` lists all 21 with status and phase.

**Docs to update** all of them.

> **Done 2026-11-05.** Heading completeness asserted by the generator.

---

### `[x]` P0.T08 — Hooks, gitignore, pre-commit, CI · **S**

**Steps** `.claude/hooks/check-module-docs.sh` as a **Stop** hook (warning, not
a block); `.githooks/pre-commit` running Pint + ESLint on staged files only;
`.gitignore`; `.github/workflows/ci.yml` with `api`, `test-mysql`, `mobile` jobs.

**Acceptance criteria**
- The hook names the module(s) whose `module.md` was not touched.
- The hook exits 0 always (never blocks).
- Pre-commit touches only staged files.
- CI runs every check, including the contrast gate.

**Docs to update** `docs/DEV_TOOLING.md` §§ 10–11.

> **Done 2026-11-05.**

---

### `[x]` P0.T09 — Graphify graph · **S**

**Steps** `graphify claude install`; build with `/graphify .`; gitignore
`graphify-out/cache/`; decide and record whether to commit the rest.

**Acceptance criteria** `graphify-out/GRAPH_REPORT.md` exists and is committed.

**Docs to update** `docs/DEV_TOOLING.md` § 7, `docs/DECISIONS.md` (ADR-014).

> **Done 2026-11-05.** Report committed, cache ignored.

---

### `[x]` P0.T10 — Connect MySQL and run the first migration · **S**

> ~~⚠️ **Blocked on a human:** the MySQL root account on this machine has a
> password. Add `DB_PASSWORD` to `api/.env` before starting.~~ **Unblocked** —
> the founder filled it in.

**Steps**
1. Set `DB_PASSWORD` in `api/.env`.
2. Create `swoon` and `swoon_test` (utf8mb4 / utf8mb4_unicode_ci).
3. `php artisan migrate` — expect users, cache, jobs, personal_access_tokens.
4. `php artisan migrate:fresh` to confirm it is repeatable.
5. Add `HasApiTokens` to `App\Models\User` (the Sanctum installer asked for it and it is still missing).
6. Add `timezone`, `locale`, `colour`, `avatar_path`, `dietary_needs`, `accessibility_needs`, `date_of_birth` to a `users` migration — the fields every later phase assumes.
7. Run `composer check`.

**Acceptance criteria**
- `php artisan migrate:fresh` succeeds against MySQL 8.
- `User` has `HasApiTokens` and can issue a token in a test.
- `composer check` passes.
- The CI `test-mysql` job passes on this schema.

**Docs to update** `api/app/Modules/Accounts/module.md` (data model), `docs/DEV_TOOLING.md` § 2.

**Size** S.

> **Done 2026-11-05.** MySQL 8.0.46; `swoon` and `swoon_test` both utf8mb4 /
> utf8mb4_unicode_ci. The profile columns went into the **base** `users`
> migration rather than a second one — nothing is deployed, so a clean schema
> beats an immediate `ALTER`. `migrate:fresh` run twice. Suite green on both
> SQLite and MySQL 8 (6 tests). Cast types needed `@property` lines on `User`;
> Larastan cannot read them off the schema, and the alternative was a
> suppression.

---

### `[x]` P0.T11 — Resend transport and the first real email · **S**

**Steps**
1. Confirm `resend/resend-laravel` registers the `resend` mailer; add it to `config/mail.php` if the package does not.
2. Add a `SwoonMailable` base class setting the from name from `config('swoon.name')`.
3. Build one trivial mailable (`TestPingMail`) with the brand voice, and an `artisan swoon:mail-test {email}` command.
4. Send with `MAIL_MAILER=log` and confirm the rendered output.
5. With a real `RESEND_API_KEY`, send to a real address.
6. Write a Pest test asserting the mailable renders and uses the configured product name.

**Acceptance criteria**
- `MAIL_MAILER=log` renders the email with the right from-name and British spelling.
- With a key, a real email arrives.
- The test suite sends nothing (`MAIL_MAILER=array` in `phpunit.xml`).

**Docs to update** `docs/DEV_TOOLING.md` § 6, `docs/ARCHITECTURE.md` § 10.

**Size** S.

> **Done 2026-11-05.** The `resend` mailer and `services.resend.key` were
> already in place from P0.T01, so step 1 was verification only. `SwoonMailable`
> sets the from-name from `config('swoon.name')`; `TestPingMail` is a markdown
> mailable on Laravel's own mail theme, so no email CSS was written and no
> vendor views were published.
>
> **Two things changed outside the steps.** Laravel's mail theme hardcodes
> `config('app.name')` in the header and footer, so `APP_NAME` now interpolates
> `${SWOON_NAME}` and the product is named in one env var (the Product block
> moved above the Laravel block, because `${}` only reads backwards). And
> `RESEND_ROUTES=false`: the package's `POST /resend/webhook` attaches its
> signature middleware only when `RESEND_WEBHOOK_SECRET` is set, so it was an
> unauthenticated public endpoint — off until webhooks are actually handled.
>
> Live send **accepted by Resend** from `hello@swoon.test`, email id
> `01a1abb8-d23b-7919-bbc8-9def8dc6797d`. The key is send-only, so the verified
> domains could not be listed from here — delivery needs confirming in the
> Resend dashboard.

---

### `[x]` P0.T12 — Queue, scheduler and Maestro harness · **S**

**Steps**
1. Add a `swoon:ping` queued job and an `artisan swoon:queue-test` that dispatches it.
2. Run `composer dev`; confirm the job is picked up and logged.
3. Register a one-minute scheduled command in `routes/console.php` that logs a heartbeat; confirm `schedule:work` fires it; remove it once seen.
4. Add `maestro/` with `smoke.yaml` as a **skeleton** asserting the app launches and Home renders the health state.
5. Document how to run Maestro in `docs/DEV_TOOLING.md`.

**Acceptance criteria**
- A dispatched job runs under `composer dev`.
- `schedule:work` fires a scheduled command.
- `maestro test maestro/smoke.yaml` passes against a dev build.
- The critical-path flow is **stubbed with TODOs** naming the phases that fill it in (sign up P1, invite P1, accept P1, plan a date P3).

**Docs to update** `docs/DEV_TOOLING.md`, `docs/ARCHITECTURE.md` § 9.

**Size** S.

> **Done 2026-11-05.** `PingJob` + `swoon:queue-test`, which dispatches and then
> reports whether a worker collected it — with no worker, a dispatch succeeds
> silently, which is what a broken setup looks like from outside. Job seen
> handled under `composer dev`; a one-minute `Schedule::call()` heartbeat seen
> firing at 13:16:00 under `schedule:work`, then removed as the task requires.
> `maestro/smoke.yaml` written, parses, asserts Phase 0 and carries the four
> critical-path steps as TODOs in dependency order.
>
> **The plan was wrong about `composer dev`, and it had never worked on this
> machine.** Pail needs the `pcntl` extension, which Windows PHP does not have,
> and it was the third process under `--kill-others` — so it killed the worker
> and the scheduler on every run. The log tail is now `composer logs`, and
> `composer dev` is queue + scheduler only. This is why P0.T01's "composer dev
> runs queue + scheduler + log tail" acceptance criterion passed on paper and
> not in reality: it was never run to completion.
>
> **One criterion is not met:** `maestro test maestro/smoke.yaml` has never
> been run. The machine has no JVM, no Android SDK, no emulator and no Maestro,
> and installing that stack is a multi-gigabyte change that is the founder's
> call, not a side effect of this task. The flow is a skeleton, as the task
> asks; what is unproven is that it passes. See `docs/DEV_TOOLING.md` § 5 for
> the five things it needs, in order.

---

## Phase 1 — Accounts & Couples

**Goal.** Two people can sign up, link into a couple, and leave cleanly. This is
the gate to everything else: no couple, no product.

**Exit criteria.**
- [x] Sign up and sign in with email, Apple and Google. *(Apple and Google are
  verified against a stubbed JWKS; no live sign-in — the credentials are unset,
  and until they are both endpoints refuse every token.)*
- [x] Invite by link **and** code; accept creates the couple.
- [x] One active couple per user, enforced in the database.
- [x] Either partner can unlink immediately, without the other's consent.
- [x] Account deletion is immediate and cascades; data export works.
- [x] The five-tab shell exists with the quick-add sheet.
- [x] **Every endpoint has an authorisation test** — and the sweep fails if a
  new route has no recorded decision, so it stays true.

**Not verified:** none of the mobile work has run on a device. There is no JVM,
no Android SDK and no emulator on this machine (see P0.T12), so every mobile
claim rests on 136 Jest tests and the type checker. The association files for
universal links are not hosted either.

**Dependencies.** Phase 0 complete (notably P0.T10).

---

### `[x]` P1.T01 — Users, couples and the one-couple invariant · **M**

**Steps**
1. Migrations: extend `users`; create `couples`, `couple_user`, `couple_invites`.
2. Enforce **one active couple per user** with a generated column + unique index (MySQL has no partial indexes), and assert it at the database level, not only in PHP.
3. Models: `User`, `Couple`, `CoupleInvite` with relationships, casts, ULIDs.
4. Factories and a `DatabaseSeeder` producing a linked couple for local work.
5. `CouplePolicy`, `UserPolicy`.
6. Tests: the invariant cannot be violated even by two concurrent accepts.

**Acceptance criteria**
- A second active `couple_user` row for the same user is rejected **by MySQL**.
- `$user->couple()` returns the active couple or null.
- Factories can build a solo user and a linked couple in one call each.
- Larastan clean; `composer check` passes.

**Docs to update** `api/app/Modules/Accounts/module.md` (data model, rules), `docs/MODULES.md`, `docs/SESSION_LOG.md`.

**Size** M.

> **Done 2026-11-05.** The invariant is a unique index on a **virtual**
> generated column, `couple_user.active_user_id`, holding `user_id` while the
> membership is live and NULL once it ends. Three things had to be right:
> `CASE` rather than MySQL's `IF()` so SQLite accepts it; VIRTUAL rather than
> STORED, because MySQL refuses `ON DELETE CASCADE` on a base column of a
> stored generated column and the migration dies with error 1215; and declared
> inside `CREATE TABLE`, because SQLite cannot add one by `ALTER TABLE`. The
> same trick gives "one open invite per user". Proven by a test that inserts
> straight into the table, bypassing the application entirely.
>
> As in P0.T10, the `users` changes went into the base migration rather than a
> second one — nothing is deployed, and a clean schema beats an immediate
> ALTER. Two things needed a line of wiring that will serve every later module:
> `Factory::guessFactoryNamesUsing` in `ModuleServiceProvider`, because Laravel
> looks for a module model's factory in `Database\Factories\Modules\…`; and
> an explicit `Gate::policy` for `User`, whose policy convention points at
> `App\Policies` while Accounts owns it. 22 tests, green on SQLite and MySQL 8.

---

### `[x]` P1.T02 — Email registration, login, logout · **M**

**Steps**
1. `RegisterRequest`, `LoginRequest` (Form Requests, British-English messages).
2. `RegisterUser` and `AuthenticateUser` Actions; issue a Sanctum token.
3. `UserResource`, `MeResource` (includes couple summary and entitlement stub).
4. Routes in `app/Modules/Accounts/routes.php`.
5. Rate-limit auth routes (throttle by IP **and** email, so one account cannot be locked out by a stranger).
6. Tests: happy paths, wrong password, duplicate email, token works on `/api/me`, logout revokes **only** the current token.

**Acceptance criteria**
- Registration returns a user and a working token.
- Wrong credentials return 422 with a non-enumerating message (never "no such user").
- `/api/me` requires a token.
- Logout revokes the current token and leaves other devices signed in.
- Password rules documented in the module doc.

**Docs to update** `Accounts/module.md` (endpoints, rules), `mobile/src/features/accounts/module.md`.

**Size** M.

> **Done 2026-11-05.** Four endpoints: register, login, logout, me. The two
> details worth the time were both about not leaking who has an account — a
> wrong password and an unknown address return the identical 422, and the
> Action hashes against a dummy rather than returning early, because a login
> that answers in 5 ms for unknown addresses and 90 ms for known ones is an
> enumeration oracle whatever the message says. Both halves asserted.
>
> The email rate limit is keyed on **email + IP**, not email alone: keyed on
> the address, a stranger could lock a real person out of their own account
> with five failed attempts. There is a test for that specifically.
>
> `PartnerResource` is a separate class from `UserResource` rather than
> conditionals in one shape — a privacy gate written as an `if` is one edit
> from leaking. `JsonResource::withoutWrapping()`, because a top-level resource
> is wrapped in `data` while a nested one is not, and half-wrapped responses
> are miserable for a typed client. Password policy is length-only (10, no
> composition rules) and documented in module.md. 18 tests.

---

### `[x]` P1.T03 — Sign in with Apple and Google · **M**

**Steps**
1. Verify the Apple identity token server-side (JWKS, audience, nonce, expiry).
2. Verify the Google ID token server-side (per-platform client ids: iOS, Android, web).
3. `FindOrCreateSocialUser` Action keyed on `apple_sub` / `google_sub`, **not** email.
4. Handle Apple private-relay emails and the name being returned **only on first authorisation**.
5. Link a social identity to an existing email account only after an explicit, deliberate confirmation.
6. Tests with stubbed JWKS: valid token, expired, wrong audience, replayed nonce, private-relay email, missing name.

**Acceptance criteria**
- A forged or expired token is rejected.
- Signing in twice with Apple yields **one** user.
- A private-relay email does not break registration.
- The real name is captured on first authorisation if offered.
- Social tokens are never used as our session token.

**Docs to update** `Accounts/module.md` (rules, gaps), `docs/DECISIONS.md` if the linking rule is non-obvious.

**Size** M. *(Needs `APPLE_*` and `GOOGLE_*` credentials.)*

> **Done 2026-11-05.** One `OidcTokenVerifier` for both providers — they both
> issue RS256 OIDC tokens — with the checks the library leaves to the caller:
> **algorithm pinned** rather than read from the token header (alg confusion),
> audience and issuer matched, and **no configured client id means refuse
> everything** rather than accept anything. Nonce replay is `Cache::add`, which
> is atomic, so claiming the nonce *is* the check.
>
> Two decisions recorded: ADR-022 for `firebase/php-jwt`, since hand-rolling
> RS256 + JWKS is where alg-confusion CVEs come from; and ADR-023 for refusing
> to link a social identity to an existing email account without explicit
> confirmation — an email match is not proof of being the same person.
>
> Two real bugs the tests caught. `apple_sub` was silently dropped because it
> is not fillable; that default is right, so the Action sets it with
> `forceFill` — a stray `User::create($request->all())` must never hand
> someone another person's identity. And my own rejection messages were
> distinguishable, so the endpoint leaked which check failed; the caller now
> gets one message and the log gets the reason.
>
> 19 tests signing real tokens with a throwaway keypair against a stubbed
> JWKS, forgeries included. **Not verified:** no live sign-in — the
> credentials are unset, and until they are both endpoints refuse every token,
> which is the right failure. Key generation needed a portable openssl-config
> lookup: PHP on Windows ships `openssl.cnf` under `extras/ssl` and does not
> point at it.

---

### `[x]` P1.T04 — Partner invite and accept · **M**

**Steps**
1. `CreateInvite` Action: 8-char code, **ambiguous characters excluded** (no 0/O, 1/I/l — partners read these aloud), 14-day expiry, one active outbound invite per user.
2. `GET /api/invites/{code}` — an unauthenticated **preview** showing only the inviter's first name.
3. `AcceptInvite` Action inside a transaction: create the couple, add both members, assign partner colours (distinct), fire `CoupleLinked`.
4. Reject: accepting your own invite; accepting while already coupled; expired; already used; revoked.
5. `RevokeInvite`.
6. `SendInviteEmail` + a single reminder after 3 days.
7. Tests for every rejection path, plus two concurrent accepts of one code.

**Acceptance criteria**
- A code is single-use; a concurrent second accept fails cleanly.
- Accepting while coupled returns a clear message, not a silent re-link.
- Partner colours differ after linking.
- `CoupleLinked` fires exactly once.
- The preview endpoint leaks **only** a first name.

**Docs to update** `Accounts/module.md`, `docs/MODULES.md`.

**Size** M.

> **Done 2026-11-05.** A couple can now be created end to end. The guard
> against two simultaneous accepts is the conditional UPDATE that claims the
> code, not the checks above it — those all pass for both callers. Creating an
> invite **revokes** any earlier open one, because the one-open-invite index
> counts an expired-but-open invite and a user whose code ran out would
> otherwise be stuck.
>
> Three bugs the tests caught, two of them mine from earlier tasks:
> `SwoonMailable` lost its constructor in the audit fixes, so `InviteMail`
> calling `parent::__construct()` was fatal; `CoupleInviteFactory::accepted()`
> called `getKey()` on a Factory; and **module Artisan commands were never
> registered** — Laravel discovers `app/Console/Commands` only, so
> `invites:remind` would have failed as a `CommandNotFoundException` from the
> scheduler rather than at boot. That is now wired in `ModuleServiceProvider`
> for every module, alongside the factory and policy wiring from P1.T01.
>
> 29 tests. 100 in the suite, green on SQLite and MySQL 8.

---

### `[x]` P1.T05 — Deep links and universal links · **M**

**Steps**
1. Expo Router route `app/invite/[code].tsx`.
2. Handle `swoon://invite/<code>` and `https://link.swoon.test/invite/<code>`.
3. Survive a **cold start** and the install detour (store the pending code, resolve after sign-in).
4. Host `apple-app-site-association` and `assetlinks.json` (document where, even if the domain is not live yet).
5. Tests: link parsing, pending-code persistence, sign-in-then-accept ordering.

**Acceptance criteria**
- A link opens Accept invite from cold start.
- An unauthenticated tap leads to sign-up, then straight to accept — the code is not lost.
- An invalid or expired code shows a warm, specific message.

**Docs to update** `mobile/src/features/accounts/module.md`, `docs/DEV_TOOLING.md` § 4.

**Size** M.

> **Done 2026-11-05.** Parsing is a **pure function**, because this is the one
> part of the app that cannot be checked by tapping around — a cold start from
> a link, and a link followed before the app existed, are both unreproducible
> by hand. It is lenient about what messaging apps do to a URL (trailing
> slashes, tracking parameters, fragments, lower case) and strict about the
> code, so a malformed one never becomes an API call.
>
> **The install detour** is the sequence that needed the work: tap the link,
> have no app, visit the store, install, open — and the cold start carries
> **no URL**. The code is stashed on sight and resolved once there is an
> account to attach it to. Kept in `expo-secure-store` because the code is a
> capability: anyone holding it can join a couple's space.
>
> A dead code gets a warm, specific message rather than a 404 screen — the
> person reading it did nothing wrong, their partner's code ran out.
>
> **Not done:** the association files are not hosted, because `link.swoon.test`
> is not a real domain and the Android fingerprint needs a release keystore
> that does not exist yet. Both paths, both file shapes and the three things
> that waste an afternoon are written up in `docs/DEV_TOOLING.md` § 4.1. The
> app side needs no change when they go up. Until then universal links fall
> back to the browser and the custom scheme is the one that works. 16 tests.

---

### `[x]` P1.T06 — Onboarding flow · **M**

**Steps**
1. Screens: Welcome → Sign up/in → Name → Timezone (detected, confirmable) → Dietary/accessibility needs → Invite partner.
2. **One question per screen**, `ProgressBar`, chips and sliders (UX rule 3).
3. Waiting-for-partner screen with nudge (24 h cooldown).
4. Persist the in-progress answers so a backgrounded app does not lose them.
5. Tests: step order, back preserves answers, skip where allowed.

**Acceptance criteria**
- No screen asks two questions.
- Back never loses an answer.
- Every non-essential question has "no preference".
- The waiting screen names the partner and offers a rate-limited nudge.

**Docs to update** `mobile/src/features/accounts/module.md`.

**Size** M.

> **Done 2026-11-05.** Needed two API endpoints that did not exist yet —
> `PATCH /api/me` and `POST /api/invites/{code}/remind` — and the
> authorisation sweep from T10 **refused to pass until both had a recorded
> decision**, which is exactly what it was built for.
>
> The step model is pure functions, so "one question per screen", "back never
> loses an answer" and "every non-essential question has a way to say nothing"
> are asserted as properties rather than as rendering.
>
> The draft lives in `expo-secure-store` on purpose, not for convenience: it
> holds dietary and accessibility needs, which can reveal religion or
> disability, and plain-text storage for a half-finished flow would be worse
> than memory. Each answer is also PATCHed as it is given, so giving up halfway
> keeps what was reached.
>
> The nudge's cooldown is **server-side**. A client-side timer is advisory, and
> the whole point of a cooldown is that it cannot become pestering — so the
> button is always offered and the server's refusal is what the person reads.
> It shares `reminded_at` with the scheduled reminder, so one suppresses the
> other rather than both arriving. 22 tests (12 API, 10 mobile).


---

### `[x]` P1.T07 — Tab shell and quick-add sheet · **M**

**Steps**
1. Expo Router tabs: Home · Dates · Moments · Planner · Us.
2. Centred "+" opening the quick-add `Sheet`: Moment · Plan · Note · Date idea.
3. Three navigation states: unauthenticated → Welcome stack; authenticated but solo → Invite stack; coupled → tabs.
4. Placeholder screens per tab using `EmptyState`.
5. Tests: the right stack renders per auth/couple state; tab labels and a11y roles.

**Acceptance criteria**
- A solo user never sees the couple tabs.
- The quick-add sheet has exactly four options.
- No hamburger menu; every deep link resolves to a tab with a back path.
- Screen-reader order checked on Home.

**Docs to update** `mobile/src/features/accounts/module.md`, `docs/UX_RULES.md` (rule 14 reference).

**Size** M.

> **Done 2026-11-05.** Taken before T05 and T06, because both navigate into
> this and building them first would have meant guessing at it.
>
> The three navigation states are a **pure function**, `authStateFor()`, so the
> rule is testable without driving a navigator — and "a solo user never sees
> the couple tabs" is worth protecting as a rule rather than as a rendering
> accident. There is a fourth state, `loading`, purely so the app does not
> flash Welcome at someone who *is* signed in on every launch; and a token the
> API **refused** counts as unauthenticated, because a token can be revoked
> from another device.
>
> The "+" floats above the tab bar rather than being a sixth tab: it is an
> action, not a place, and a tab that does not navigate confuses
> screen-reader users most of all. The sheet's four options are a `const` array
> with a test on its length.
>
> Two dependencies avoided and one added: `expo-secure-store` was already
> mandated by CLAUDE.md, so it went in; `expo-localization` did not, because
> `Intl.DateTimeFormat().resolvedOptions().timeZone` is the same answer from
> the platform. 12 tests, 110 mobile tests total.

---

### `[x]` P1.T08 — Unlink · **S**

**Steps**
1. `UnlinkCouple` Action: set `couple_user.left_at` and `couples.unlinked_at`, **immediately**, no consent from the other partner.
2. Detach rather than delete: each partner keeps what they authored; neither can read shared content afterwards.
3. Fire `CoupleUnlinked`; email the other partner plainly and without blame.
4. Mobile: one plain-words confirm (UX rule 10) stating exactly what happens, including the billing consequence.
5. Tests: either side can unlink; shared content unreadable after; authored content retained; the other partner is notified; a new invite can be created afterwards.

**Acceptance criteria**
- Unlink needs no consent and takes effect on the same request.
- Neither ex-partner can read the couple's shared content.
- The confirm copy says what happens to data **and** to the subscription.

**Docs to update** `Accounts/module.md` (rules), `api/app/Modules/Billing/module.md` (the entitlement-follows-payer rule).

**Size** S.

> **Done 2026-11-05.** Taken out of order, before T05–T07: those three are
> mobile, and doing the backend first lets T10's sweep cover every endpoint in
> one pass.
>
> `DELETE /api/couple` with **no couple id in the route** — a user has at most
> one and the database guarantees it, so an id would only create a way to ask
> about somebody else's. Ending both memberships also releases the one-couple
> invariant, so either partner can link again immediately; leaving must not be
> a one-way door out of the product, and there is a test for that.
>
> The notice email has a test asserting that "why", "undo", "contact", "will
> be" and friends are **absent**: any of them turns a safety feature into a
> negotiation. It carries strings rather than a `User`, because "unlink, then
> delete my account" is exactly the sequence someone leaving performs.
>
> One more piece of module wiring, and a trap avoided: module **listeners** are
> not auto-discovered either. Scanning `app/Modules` wholesale registered every
> **Action** as a listener for its own first parameter's type, so discovery is
> scoped to `app/Modules/*/Listeners` by glob.
>
> **Not built:** the mobile confirm screen, which arrives with the tab shell in
> P1.T07. Its required copy — including the billing consequence — is written
> into `Accounts/module.md` so it cannot be forgotten. 15 tests, 115 total.

---

### `[x]` P1.T09 — Account deletion and data export · **M**

**Steps**
1. `DeleteAccount`: respond fast, then `PurgeDeletedUserData` on the queue.
2. Cascade own moments, answers, notes, vault items, media objects, tokens; reduce attribution on surviving shared items to a tombstone.
3. `BuildDataExport`: JSON + original media, uploaded, delivered by a **signed, expiring** link.
4. Decide and **document the export JSON shape** (listed as a gap in the module doc).
5. Mobile: Privacy & data screen with export and delete, one plain confirm each.
6. Tests: deletion removes every own-authored row (one assertion per table); export contains everything the user authored plus shared content they could see, and **nothing the partner marked private**; the export link expires.

**Acceptance criteria**
- Deletion is immediate from the user's perspective and complete within the job.
- Nothing of the deleted user remains beyond tombstones.
- The export omits the partner's private data.
- The export link expires and is not guessable.

**Docs to update** `Accounts/module.md` (rules, gaps closed), `docs/ARCHITECTURE.md` § 3.1.

**Size** M.

> **Done 2026-11-05.** Deletion revokes every token, unlinks the couple and
> kills outstanding invites *inside the request*, then tombstones on the queue.
> A **tombstone rather than a hard delete**, because the other partner's shared
> content has to keep resolving — a couple's memories are two people's
> memories. The purge job refuses to touch an account that is not soft-deleted:
> a stale retry scrubbing a live account would be the worst bug in the system,
> so it has its own test.
>
> **The export JSON shape is settled**, closing the gap the module doc had been
> carrying since P0.T07. `version` is the contract. The partner appears as a
> name and a colour and nothing else, asserted against the bytes rather than a
> screen — "the export omits the partner's private data" is not something a
> UI test can prove.
>
> The download link is signed and 48-hour, **and** the route checks the caller
> owns it: a signature proves we minted the URL, not that the holder is the
> right person, and these links travel by email. There is a test for a
> forwarded link and one for path traversal through the signed parameter.
>
> Most of what rule 9 lists does not exist yet, so `PurgeDeletedUserData` and
> `BuildDataExport` carry TODOs naming the phase that creates each table. Both
> are deliberately explicit lists, not a registry: a visible list that fails
> review beats a mechanism that silently misses a table.
>
> **Not built:** the Privacy & data screen, which arrives with the shell in
> P1.T07. 22 tests, 137 total.

---

### `[x]` P1.T10 — Authorisation test sweep for Phase 1 · **S**

**Steps**
1. A `tests/Feature/Authorisation` helper building two unrelated couples.
2. For **every** Accounts route, assert a foreign user gets 403/404.
3. Assert an unauthenticated request to a protected route gets 401.
4. Make the sweep a pattern later phases copy.

**Acceptance criteria**
- Every Accounts endpoint has a negative-authorisation test.
- The helper is reusable by later phases with one line.

**Docs to update** `Accounts/module.md` (tests), `docs/ARCHITECTURE.md` § 9.

**Size** S.

> **Done 2026-11-05.** The sweep is **driven off the real route table**, not a
> hand-written list: every Accounts route must have a recorded decision and the
> suite fails naming any that does not. Verified by temporarily adding a route
> and watching it fail — a list of assertions passes forever while the route
> table grows past it.
>
> Naming the right kind was most of the work. The couple routes are not
> `owned`: they take **no id**, so an outsider cannot aim them at another
> couple at all — they get their own. Classifying them as `owned` produced a
> failing test that was *my classification* being wrong, not the code. They are
> `self`, whose negative case is a signed-in user with no couple, plus a
> structural assertion that the URI contains no `{`.
>
> The outsider in `TwoCouples` is a member of a real, live couple rather than a
> solo user, because a solo user is refused by accident for having no couple at
> all. There is a positive control too: a file that only asserts refusals
> passes with every endpoint broken. 12 tests, 149 total.

---

## Phase 2 — AI platform

**Goal.** One safe, cheap, testable door to Claude. Nothing user-facing ships
this phase; everything after it depends on this being right.

**Exit criteria.**
- `AiService::run()` is the only path to the model.
- Prompts are versioned files, pinned in config.
- Structured output validated; malformed output handled.
- **SafetyGuard works, fails closed, and is independently tested.**
- Cost tracked per call; caps enforced before dispatch.
- The whole suite runs with **no network and no spend**.

**Dependencies.** Phase 1 (needs couples to scope usage and caps).

---

### `[x]` P2.T01 — AI driver abstraction and the fake · **M**

**Steps**
1. `AiDriver` interface: `generate(prompt, schema, options): AiResult`.
2. `AnthropicDriver` on `laravel/ai`, model and effort from `config('ai.tasks')`.
3. `FakeDriver` returning fixtures from `tests/Fixtures/Ai`, recording calls for assertions.
4. Bind by `config('ai.driver')`.
5. **Re-evaluate** whether `laravel/ai`'s own fake replaces `FakeDriver` — record the outcome in ADR-007.
6. Tests: driver selection; the fake records prompt, task and payload; the real driver is never constructed under `AI_DRIVER=fake`.

**Acceptance criteria**
- `AI_DRIVER=fake` guarantees no network call (asserted, not assumed).
- Model ids appear nowhere outside `config/ai.php`.
- A test can assert *which* prompt and payload were used.

**Docs to update** `api/app/Modules/Ai/module.md`, `docs/DECISIONS.md` (ADR-007 outcome).

**Size** M.

---

### `[x]` P2.T02 — Prompt registry with versioning · **S**

**Steps**
1. `resources/prompts/<name>/<version>.md`.
2. `PromptRegistry::resolve(name)` reading the **pinned** version from `config('ai.prompts.active')`.
3. Simple variable interpolation; fail loudly on a missing variable rather than sending an empty slot.
4. Tests: pinned version is used even when a newer file exists; unknown prompt throws; missing variable throws.

**Acceptance criteria**
- Adding `v2.md` changes nothing until config is updated.
- A missing variable is an exception, never a silently empty prompt.
- The resolved version is recorded on every `ai_usage` row.

**Docs to update** `Ai/module.md`, `docs/AI_PROMPTS.md`.

**Size** S.

---

### `[ ]` P2.T03 — Structured output validation · **S**

**Steps**
1. Declare a JSON schema per task.
2. `SchemaValidator` validating the model's response before anything else sees it.
3. Retry **once** on invalid output, then record `outcome = invalid_output`.
4. Tests: valid passes; missing field fails; wrong type fails; extra fields rejected or ignored (decide and document); retry happens exactly once.

**Acceptance criteria**
- Malformed output never reaches a couple.
- Exactly one retry, then a friendly failure.
- The failure is logged with tokens and cost (we paid for it).

**Docs to update** `Ai/module.md`, `docs/AI_PROMPTS.md`.

**Size** S.

---

### `[ ]` P2.T04 — SafetyGuard · **M**

> **The most important task in the project.** Treat it accordingly.

**Steps**
1. `safety_screen` v1 prompt returning `{ concern, confidence }` only.
2. `SafetyGuard` step: runs on **every** user-facing generation, on both input free-text and draft output.
3. **Fail closed:** `unclear` counts as a concern; an error in the guard abandons the generation.
4. On a concern: discard the normal output **entirely** and return only the signpost from `config('ai.safety.signposts')`.
5. **Store nothing** about what triggered it — `outcome = safety_tripped`, no content.
6. Never surface one partner's concern to the other.
7. Build the fixture battery: crisis, abuse, self-harm, harm-to-others, including indirect and euphemistic phrasings — **plus a large benign set** to measure false positives.
8. Tests for each category, for fail-closed behaviour, and for "a normal argument does not trip it".

**Acceptance criteria**
- Every crisis/abuse fixture returns **only** the signpost.
- A guard error produces no output at all.
- Benign fixtures (including ordinary rows and grumbles) do not trip it.
- No triggering content is persisted anywhere.
- A concern is never revealed to the partner.

**Docs to update** `Ai/module.md`, `docs/AI_PROMPTS.md` (`safety_screen`).

**Size** M.

> **Standing gap:** the battery is a developer's best effort and **needs review
> by someone with relevant expertise before launch.** Keep it listed in the
> module's gaps until that happens.

---

### `[ ]` P2.T05 — Privacy filter (allow-list) · **M**

**Steps**
1. `PrivacyFilter` built from a **declared allow-list per task** — never a deny-list.
2. Declare the allow-list for every task in `config/ai.php` or a per-task class.
3. Anything not allow-listed is dropped before the prompt is rendered.
4. Tests: **one test per private field** asserting it cannot reach a payload (gift vault, check-in answers, private moments, private love-map entries, locked love notes, un-revealed answers, emails, full postcodes, shipping addresses).
5. A test proving a **newly added** column is excluded by default.

**Acceptance criteria**
- Each private field has its own failing-if-leaked test.
- A new column added to a model does not appear in any payload without being allow-listed.
- The allow-list per task is visible in one place a reviewer can read.

**Docs to update** `Ai/module.md` (privacy), `docs/ARCHITECTURE.md` § 4.2.

**Size** M.

---

### `[ ]` P2.T06 — Cost tracking and `ai_usage` · **S**

**Steps**
1. Migration `ai_usage` (indexed on `couple_id, created_at`).
2. `UsageLogger` writing on **every** outcome, including failures.
3. Compute `cost_pence` from `config('ai.pricing_usd_cents_per_mtok')`, `config('ai.usd_to_pence')` and the returned token counts. The prices are in US cents and every budget in the app is in pence — convert, never compare directly.
4. Migration `ai_generations` (+ `superseded_by`) and `ai_feedback`.
5. Tests: a row per call including failures; cost arithmetic; regenerate supersedes rather than overwrites.

**Acceptance criteria**
- No code path reaches the model without logging.
- Cost is comparable over time even after provider prices change.
- History is preserved when a prompt version changes.

**Docs to update** `Ai/module.md`, `docs/PRICING.md` § 7.

**Size** S.

---

### `[ ]` P2.T07 — Caps and the cost guard · **M**

**Steps**
1. `CapGuard` checked **before dispatch**: entitlement tier → `config('ai.caps')` → monthly count from `ai_usage`.
2. Runaway guard: per-couple monthly pence and global daily pence.
3. A kind, specific refusal including the reset date — **never** a 429 or an error.
4. `GET /api/ai/allowance` so the app can show what is left.
5. Alert when the global guard trips.
6. Tests: cap boundary (n-1, n, n+1); month rollover in the **couple's** timezone; the refusal is kind; a capped request costs nothing.

**Acceptance criteria**
- A capped request makes no model call.
- Boundary arithmetic is correct across a month rollover.
- Refusal copy names the reset date and has no guilt (UX rule 8).
- Crossing the global guard alerts and refuses.

**Docs to update** `Ai/module.md`, `docs/PRICING.md` § 7.

**Size** M.

---

### `[ ]` P2.T08 — Queued `RunAiTask` and the full pipeline · **M**

**Steps**
1. `RunAiTask` job wiring the whole chain: CapGuard → PrivacyFilter → PromptRegistry → Driver → SchemaValidator → SafetyGuard → UsageLogger → persist.
2. Retry once on transport error; never retry a safety trip.
3. Events: `AiGenerationCompleted`, `AiGenerationFailed`, `AiSafetyTripped`.
4. Feedback and regenerate endpoints.
5. `ai:report-costs` scheduled command.
6. End-to-end test with the fake driver covering each terminal outcome.

**Acceptance criteria**
- **No AI call happens in an HTTP request** (asserted).
- Each outcome (`ok`, `invalid_output`, `safety_tripped`, `capped`, `error`) has a test.
- Regenerate counts against the cap and supersedes the old generation.

**Docs to update** `Ai/module.md` (jobs, events), `docs/ARCHITECTURE.md` § 4.

**Size** M.

---

### `[ ]` P2.T09 — Eval fixtures and the prompt-change workflow · **S**

**Steps**
1. `tests/Fixtures/Ai/evals/<prompt>/` with input payloads and shape assertions.
2. An `artisan swoon:ai-eval {prompt}` command runnable against the real model, **explicitly opt-in**, that reports cost.
3. Document the change workflow (copy to a new version, update evals, compare, promote, bump `AI_PROMPTS.md`).
4. Tests: evals run under the fake driver in CI; the live command refuses without an explicit flag.

**Acceptance criteria**
- CI runs the evals with no network and no cost.
- A live eval run is deliberate, never accidental, and prints what it spent.
- The workflow is written down in `AI_PROMPTS.md`.

**Docs to update** `docs/AI_PROMPTS.md`, `Ai/module.md` (tests).

**Size** S.

---

## Phase 3 — AI Date Planner

**Goal.** The headline feature: both partners answer independently, answers stay
hidden until both are in, Claude proposes three fair options, the couple picks
one and it gets scheduled with reminders.

**Exit criteria.**
- The reveal gate is enforced **in the API** and tested.
- Three options with every required field, every time.
- `both_heart` and `either_locks` both work as a couple setting.
- Scheduling creates a planner event with local notifications and an email reminder.

**Dependencies.** Phases 1 and 2.

---

### `[ ]` P3.T01 — Date plan schema and models · **S**

**Steps** Migrations for `date_plan_requests`, `date_plan_answers` (**outcode
only**, never a full postcode), `date_plan_options`, `date_plan_hearts`. Models,
factories, `DatePlanPolicy`. Add `lock_in_mode` to `couples`.

**Acceptance criteria**
- Unique on (request, user) for answers.
- A full postcode cannot be stored (validated and tested).
- `lock_in_mode` defaults to `both_heart`.

**Docs to update** `api/app/Modules/DatePlanner/module.md`.

**Size** S.

---

### `[ ]` P3.T02 — The reveal gate · **M**

> The product's core promise. Build and test this **before** the questionnaire UI.

**Steps**
1. `StartDatePlan` and `SubmitAnswers` Actions.
2. `DatePlanResource` returning the other partner's answers **only** when both `submitted_at` are set.
3. Before the reveal, return a progress indicator (`1 of 2`) and nothing else.
4. Tests: the other partner's answers are **absent from the JSON** on detail, index, and after a regenerate; present after both submit; a partner can edit only their own answers.

**Acceptance criteria**
- No endpoint returns the other partner's answers before both have submitted.
- Writing another partner's answers is 403.
- The progress indicator leaks nothing (no counts of options chosen, no preview).

**Docs to update** `DatePlanner/module.md` (rules), `docs/DECISIONS.md` (ADR-005 reference).

**Size** M.

---

### `[ ]` P3.T03 — The `date_plan` prompt and generation job · **M**

**Steps**
1. Write `resources/prompts/date_plan/v1.md` per `docs/AI_PROMPTS.md`.
2. Declare the allow-list: both answer sets, both partners' dietary and accessibility needs, first names, recent date titles/vibes, optional bucket-list items.
3. `GenerateDatePlan` job dispatched by the **second** submission (not a button).
4. Validate exactly 3 options with every field.
5. Fixtures: aligned, mildly conflicting, **sharply conflicting**, tight budget, no car, dietary restriction, accessibility need, minimal answers.
6. Tests: generation fires exactly once; dietary needs present in the payload; three options always; invalid output handled.

**Acceptance criteria**
- Generation triggers on the second submission, once, even on a double-submit.
- Dietary and accessibility needs are in the payload for both partners, always.
- Exactly three options, each with title, emoji, summary, timeline, cost, travel notes, `why_both`.
- No private data in the payload (reuses the P2.T05 tests).

**Docs to update** `DatePlanner/module.md` (AI usage), `docs/AI_PROMPTS.md`.

**Size** M.

---

### `[ ]` P3.T04 — Questionnaire UI · **M**

**Steps**
1. One question per screen: when → area/outcode → duration → driving → budget → vibe → romance (1–5) → energy (1–5) → notes.
2. Chips and sliders; `ProgressBar`; big targets.
3. Draft locally so the questionnaire works **fully offline**; submit on reconnect.
4. Back preserves answers; "no preference" on every optional question.
5. Tests: step order, offline draft, submit, a11y labels on sliders.

**Acceptance criteria**
- Three taps from Home to the first question.
- Works offline end to end up to submission.
- No screen asks two questions.
- Every slider has an accessible value announcement.

**Docs to update** `mobile/src/features/date-planner/module.md`.

**Size** M.

---

### `[ ]` P3.T05 — Waiting, nudge and reveal · **M**

**Steps**
1. Waiting screen naming the partner, with `AvatarPair` in pending state.
2. `POST /api/date-plans/{id}/nudge` with a **24 h cooldown per couple per item**; button becomes "Nudged ✅".
3. `SendDatePlanNudgeEmail`.
4. Reveal screen showing both answer sets side by side in partner colours, with the celebratory animation (respecting reduce-motion).
5. Tests: cooldown enforced server-side; reveal renders only after both; reduce-motion path.

**Acceptance criteria**
- A second nudge inside 24 h is refused by the API, not just the UI.
- Nudge copy implies no fault.
- The reveal animation becomes a cross-fade under reduce-motion.

**Docs to update** `DatePlanner/module.md`, `mobile/.../date-planner/module.md`.

**Size** M.

---

### `[ ]` P3.T06 — Options, hearts and lock-in · **M**

**Steps**
1. Three option cards with `AITag`, regenerate, thumbs up/down.
2. Heart / un-heart endpoints.
3. `both_heart`: scheduling requires both hearts. `either_locks`: either partner may schedule alone.
4. Couple setting UI in the Us tab.
5. Regenerate counts against the cap and supersedes.
6. Tests: both modes; scheduling refused without the required hearts in `both_heart`; regenerate cap accounting.

**Acceptance criteria**
- `both_heart` blocks a solo schedule; `either_locks` allows it.
- Changing the setting does not retroactively unlock a past plan.
- Every option card is AI-labelled and ratable.

**Docs to update** `DatePlanner/module.md` (rules).

**Size** M.

---

### `[ ]` P3.T07 — Reminders module core · **M**

**Steps**
1. Migrations `reminders`, `reminder_preferences` (per **user**, not per couple).
2. Listen for `PlannerEventCreated|Updated|Deleted` and `DatePlanChosen`; write and cancel reminder rows.
3. `GET /api/reminders/schedule` returning a **complete desired state**, not a diff.
4. Compute times in the **user's** timezone; all-day events at 09:00 local.
5. `reminders:dispatch` every 15 minutes for email-channel reminders; `email_sent_at` makes it idempotent.
6. Tests: complete desired state; DST boundary; email sent once; cancellation cascades; no reminder for a past event.

**Acceptance criteria**
- The schedule endpoint is idempotent and self-healing after a missed sync.
- A DST transition does not shift a reminder.
- An email reminder is never sent twice.
- Turning reminders off is honoured with no "important" override.

**Docs to update** `api/app/Modules/Reminders/module.md`.

**Size** M.

---

### `[ ]` P3.T08 — Local notifications on device · **M**

**Steps**
1. Request notification permission **in context** — the first time the user schedules something, never on first launch.
2. On foreground and after any planner/date mutation: fetch the schedule, cancel our own scheduled notifications, re-schedule from the response.
3. Respect the **iOS 64 pending-notification cap**: schedule the nearest 60, re-fill on each sync.
4. Reminder settings screen.
5. Tests: re-schedule is idempotent; the cap is respected; permission is requested in context; a denied permission degrades gracefully to email only.

**Acceptance criteria**
- Re-syncing twice does not duplicate notifications.
- Never more than 60 pending Swoon notifications on iOS.
- Denying permission leaves the app fully usable.

**Docs to update** `mobile/src/features/reminders/module.md`.

**Size** M.

---

### `[ ]` P3.T09 — Schedule a date end to end · **S**

**Steps**
1. `POST /api/date-plan-options/{id}/schedule` creating a `planner_events` row with `source = date_plan`.
2. "Date locked in 🎉 We'll nudge you both the day before." + confetti.
3. Email both partners.
4. Extend the Maestro smoke flow: sign up → invite → accept → plan a date.
5. Tests: event created and linked; reminders created; both partners emailed.

**Acceptance criteria**
- The Maestro critical-path flow passes.
- The scheduled date appears in the Planner tab with a countdown.
- Reminders exist for both partners at their own lead times.

**Docs to update** `DatePlanner/module.md`, `docs/ARCHITECTURE.md` § 9, `docs/DEV_TOOLING.md`.

**Size** S.

---

## Phase 4 — Moments & On This Day

**Goal.** The shared archive, offline-tolerant, with private media and a playful
resurfacing card. This is what makes the app worth keeping.

**Exit criteria.**
- A moment can be created with text alone, offline, three taps from Home.
- Photos and voice notes on a private bucket, signed URLs only, **EXIF/GPS stripped**.
- Private moments invisible to the partner everywhere, including AI.
- Delete is undoable for 10 seconds.
- On This Day works, including the 29 February case.

**Dependencies.** Phase 1. (Phase 2 only for optional tagging.)

---

### `[ ]` P4.T01 — Moments schema and CRUD · **M**

**Steps** Migrations `moments`, `moment_media`, `tags`, `moment_tags`
(couple-scoped tags). Models, factories, `MomentPolicy` (view: both unless
private; write: author only). CRUD endpoints with Form Requests and Resources.
Accept a **client ULID** as an idempotency key.

**Acceptance criteria**
- A moment needs only `body`.
- Only the author can edit or delete.
- Posting the same client ULID twice creates **one** moment.
- A foreign couple gets 404 on every route.

**Docs to update** `api/app/Modules/Moments/module.md`.

**Size** M.

---

### `[ ]` P4.T02 — Private moments · **S**

**Steps** `is_private` honoured in index, detail, search, On This Day, export and
every AI payload. Lock badge + "Only you can see this" in the UI.

**Acceptance criteria**
- A private moment is **absent from the JSON** on every partner-facing endpoint (one test each).
- It is excluded from the partner's export.
- It never appears in an AI payload (reuses P2.T05).
- Ambiguous cases default to private.

**Docs to update** `Moments/module.md` (privacy), `docs/ARCHITECTURE.md` § 3.1.

**Size** S.

---

### `[ ]` P4.T03 — Media upload and signed URLs · **M**

**Steps**
1. `POST /api/moments/{id}/media` returning a signed upload target.
2. Private bucket; S3 in production, `local` disk in dev.
3. Short-lived signed read URLs (`MEDIA_SIGNED_URL_TTL_MINUTES`, default 15).
4. Uploads are **separate from the record**, so text lands while media uploads.
5. Tests: no public URL exists; signed URLs expire; a pending upload does not block the moment; per-tier photo limits.

**Acceptance criteria**
- Media is unreachable without a valid signed URL.
- A moment is readable while its photo is still uploading, and says so.
- The signed URL TTL comes from config.

**Docs to update** `Moments/module.md`, `docs/ARCHITECTURE.md` § 6.

**Size** M.

---

### `[ ]` P4.T04 — Photo and voice processing · **M**

**Steps**
1. `ProcessMomentPhoto`: display size, thumbnail, **strip EXIF including GPS**; keep the original for the book.
2. `TranscodeVoiceNote`: single compact format, 60 s cap.
3. Tests: **a photo with GPS EXIF comes out with none** (the important one); thumbnail exists; original retained; an over-length voice note is rejected kindly.

**Acceptance criteria**
- GPS EXIF is provably removed — a product that promises no location tracking must not leak one through a photo.
- Originals survive for print.
- Processing failures do not lose the moment.

**Docs to update** `Moments/module.md` (jobs, privacy).

**Size** M.

---

### `[ ]` P4.T05 — Undoable delete · **S**

**Steps** Soft delete; `CommitMomentDeletion` after
`config('swoon.undo.moment_delete_seconds')`; restore endpoint; snackbar undo in
the app; `moments:purge-deleted` hard-deleting after 30 days **with its media**.

**Acceptance criteria**
- The row leaves the feed immediately and is restorable for 10 s.
- After the window it is committed.
- Purge removes the media too, not just the row.

**Docs to update** `Moments/module.md`, `mobile/.../moments/module.md`.

**Size** S.

---

### `[ ]` P4.T06 — Offline create and sync · **M**

**Steps**
1. A local write queue with client ULIDs.
2. Optimistic insert, "Saving…" → "Saved".
3. Media queued separately from the record.
4. Permanent failure keeps the item local **and says so** — nothing silently disappears.
5. Tests: offline create then sync; **no duplicate on retry**; media queued independently; permanent failure surfaced.

**Acceptance criteria**
- Creating offline then reconnecting produces exactly one moment.
- Killing the app mid-queue loses nothing.
- A failed item is visible and retryable, never a silent loss.

**Docs to update** `mobile/src/features/moments/module.md`, `docs/ARCHITECTURE.md` § 7.

**Size** M.

---

### `[ ]` P4.T07 — Feed, search and tags · **M**

**Steps** Paginated feed with `AvatarPair` attribution; filter by tag and date;
search own + shared (never the partner's private); couple-scoped tag vocabulary;
warm empty state.

**Acceptance criteria**
- The feed shows who added each moment (avatar + colour, never colour alone).
- Search never returns the partner's private moments.
- The empty state is emoji + one sentence + one button.
- Pagination is stable while new moments arrive.

**Docs to update** `Moments/module.md`, `mobile/.../moments/module.md`.

**Size** M.

---

### `[ ]` P4.T08 — On This Day logic · **M**

**Steps**
1. The bucket ladder: same day previous years (newest first) → 1 year → 6 months → 1 month → warm empty.
2. **29 February:** a 29 Feb moment surfaces on 28 Feb in non-leap years.
3. Evaluate in the **user's** timezone.
4. Exclude private moments **even from their author** (it is a shared home screen).
5. Decide the no-repeat strategy (seen-log vs deterministic rotation) and record it.
6. Index `moments` on (couple_id, happened_on).
7. Tests: each rung of the ladder; 29 Feb; timezone boundary; private excluded; no repeat on consecutive days.

**Acceptance criteria**
- The ladder picks the first bucket with content.
- A 29 Feb memory is not lost for three years.
- The card changes at the user's midnight, not the server's.
- Private moments never surface.

**Docs to update** `api/app/Modules/OnThisDay/module.md` (rules, gaps closed).

**Size** M.

---

### `[ ]` P4.T09 — On This Day card and opt-in notification · **S**

**Steps** Home card (`Card`, playful copy, tap through to the moment); "see all
from this day"; opt-in daily local notification at a chosen time, **silent when
there is nothing**; preferences endpoint.

**Acceptance criteria**
- The card is the top thing on Home.
- The notification never fires just to say "nothing today".
- Opt-in is off by default.
- The card renders from cache on a cold offline start.

**Docs to update** `mobile/src/features/on-this-day/module.md`.

**Size** S.

---

## Phase 5 — Planner & Milestones

**Goal.** A shared calendar that either partner can fix, with countdowns and
anniversaries that look after themselves.

**Exit criteria.** Either partner can manage any event; all-day events do not
drift across timezones; milestones generate yearly events idempotently; "days
together" is correct and explains itself.

**Dependencies.** Phases 1 and 3 (Reminders).

---

### `[ ]` P5.T01 — Planner events · **M**

**Steps** Migration `planner_events` (category, `is_all_day`, own `timezone`,
`source`/`source_id`); CRUD where **either partner** may write; countdown in the
viewer's timezone; offline create with client ULID; emit events for Reminders.

**Acceptance criteria**
- Either partner can edit and delete any event.
- An **all-day event does not drift** across a timezone change (stored as a date, not a midnight timestamp).
- A timed event keeps its own timezone.
- Deleting cancels its reminders.

**Docs to update** `api/app/Modules/Planner/module.md`.

**Size** M.

---

### `[ ]` P5.T02 — Planner UI · **M**

**Steps** Planner tab (Upcoming / Past), event detail, add/edit sheet, category
chips, countdown display, warm empty state, offline create.

**Acceptance criteria**
- Past events remain visible in their own section.
- Countdown shows days, or hours under a day.
- One primary action per screen.
- Create works offline.

**Docs to update** `mobile/src/features/planner/module.md`.

**Size** M.

---

### `[ ]` P5.T03 — Milestones · **M**

**Steps** Migration `milestones`; keep `couples.anniversary_on` /
`first_date_on` **in sync** with the matching milestone; seed an anniversary on
`CoupleLinked`; birthday milestones linked to a user; CRUD for either partner.

**Acceptance criteria**
- The couple record and the milestone never disagree.
- A birthday milestone leaves with its user.
- Either partner can manage milestones.

**Docs to update** `api/app/Modules/Milestones/module.md`.

**Size** M.

---

### `[ ]` P5.T04 — Yearly event generation · **S**

**Steps** `GenerateMilestoneEvents` for the next **12 months only**, idempotent
on (milestone, year); `milestones:generate` daily top-up; 29 February observed
on 28 February in non-leap years; longer default reminder lead (7 days).

**Acceptance criteria**
- Running generation twice creates nothing extra.
- Only a rolling 12-month window exists — no decades of rows.
- A 29 Feb anniversary is observed every year.

**Docs to update** `Milestones/module.md` (jobs).

**Size** S.

---

### `[ ]` P5.T05 — Days-together counter · **S**

**Steps** Compute from `first_date_on` → else `anniversary_on` → else
`linked_at`; **the card says which source it used**; compute locally from cached
dates so it is right offline.

**Acceptance criteria**
- Precedence is correct and tested.
- The number is never mysterious — the card names its basis.
- Correct with no network.

**Docs to update** `Milestones/module.md`, `mobile/.../milestones/module.md`.

**Size** S.

---

### `[ ]` P5.T06 — Authorisation sweep for Phases 3–5 · **S**

**Steps** Apply the P1.T10 helper to every DatePlanner, Reminders, Moments,
OnThisDay, Planner and Milestones route.

**Acceptance criteria** Every endpoint in those modules has a negative-authorisation test; no gaps.

**Docs to update** each module's Tests section.

**Size** S.

---

## Phase 6 — Engagement

**Goal.** Reasons to open the app daily that never feel like homework.

**Exit criteria.** Daily Spark with a working reveal gate; weekly challenges with
**pausing** streaks; Love Notes whose contents genuinely cannot leak early; Date
Roulette with **zero** AI cost.

**Dependencies.** Phases 1, 4 (Moments, for challenge → moment).

---

### `[ ]` P6.T01 — Daily Spark question library · **S**

**Steps** Migration `spark_questions`; seed **~200** questions reviewed for tone;
`spark_question` v1 as an **authoring** tool only (human-reviewed before
seeding); seasonal windows.

**Acceptance criteria**
- ≥200 active questions seeded.
- **No question assumes gender, cohabitation, children, income or life stage** — an explicit review pass, documented.
- Seasonal questions are only eligible in their window.

**Docs to update** `api/app/Modules/DailySpark/module.md` (gaps closed), `docs/AI_PROMPTS.md`.

**Size** S. *(Writing 200 good questions is the bulk of this; split if it runs long.)*

---

### `[ ]` P6.T02 — Daily Spark assignment and reveal · **M**

**Steps** Migrations `spark_prompts`, `spark_answers`; `AssignDailySpark`
idempotent on (couple, local date); `spark:assign` hourly for local midnights;
**API withholds the other answer until both have answered**; reveal animation;
no repeat until the library is exhausted.

**Acceptance criteria**
- The other partner's answer is **absent from the JSON** until both have answered, on today and history.
- One question per couple per local day, idempotent.
- No repeat until exhaustion, then reshuffle.
- A missed day creates no penalty and no badge.
- Answers editable before reveal, not after.

**Docs to update** `DailySpark/module.md`, `mobile/.../daily-spark/module.md`.

**Size** M.

---

### `[ ]` P6.T03 — Daily Spark UI · **S**

**Steps** Home card; answer sheet (**two taps**); waiting state with nudge;
reveal; history.

**Acceptance criteria**
- Two taps from Home to answer.
- Waiting shows "1 of 2 answered", never a preview.
- Reveal respects reduce-motion.

**Docs to update** `mobile/src/features/daily-spark/module.md`.

**Size** S.

---

### `[ ]` P6.T04 — Challenge library · **S**

**Steps** Migration `challenges`; seed **~150** reviewed challenges across
efforts; `requires_both` flag; seasonal windows.

**Acceptance criteria**
- ≥150 active challenges, with a good spread of `tiny`.
- **None requires spending money, cohabitation, children or physical ability** — explicit review pass.
- Seasonal eligibility works.

**Docs to update** `api/app/Modules/Challenges/module.md` (gaps closed).

**Size** S.

---

### `[ ]` P6.T05 — Weekly challenges and gentle streaks · **M**

**Steps** Migrations `couple_challenges`, `challenge_completions`, `streaks`;
`AssignWeeklyChallenges` idempotent per couple per local week (three per week,
always including a `tiny`); completion, undo, and an **offer** to create a
Moment; **streaks pause, never break**.

**Acceptance criteria**
- Three challenges per week, assigned at the couple's local week start, idempotent.
- `requires_both` completes only when both mark it.
- **A quiet week sets `paused_at` and preserves `current_length`** — it is never zeroed.
- No penalty state, no red badge, no shame copy exists anywhere in the module.
- Completion never silently creates a Moment.

**Docs to update** `Challenges/module.md`, `mobile/.../challenges/module.md`.

**Size** M.

---

### `[ ]` P6.T06 — Love Notes · **M**

**Steps**
1. Migration `love_notes`.
2. **The body is never returned to the recipient before `unlocks_at`** — not in index, detail, notification payload or email.
3. `GET /api/love-notes/schedule` returning **only** unlock time and author.
4. `love-notes:unlock` every 15 minutes; content-free "a note is ready" email.
5. Author may edit/cancel before unlock, never after; a cancelled note leaves **no trace** for the recipient.
6. A past unlock date unlocks immediately.
7. Tests: body absent on every recipient-facing path; recipient timezone honoured; cancellation leaves no trace; the email contains no note text.

**Acceptance criteria**
- There is **no** API path that returns a locked note's body to the recipient.
- The notification body is contentless ("A note from Sam is ready 💕").
- A cancelled note is invisible — the recipient never learns one existed.

**Docs to update** `api/app/Modules/LoveNotes/module.md`.

**Size** M.

---

### `[ ]` P6.T07 — Love Notes UI and local notifications · **S**

**Steps** List (Scheduled by me / For me); write screen with date picker; locked
state showing only author + unlock date; opening animation; local notification
scheduled from the content-free schedule endpoint.

**Acceptance criteria**
- A locked note holds **no content on the device** — nothing cached to leak.
- Writing works offline and queues.
- The unlock notification carries no content.

**Docs to update** `mobile/src/features/love-notes/module.md`.

**Size** S.

---

### `[ ]` P6.T08 — Date idea library and Date Roulette · **M**

**Steps** Migration `date_ideas`; seed **~200** ideas across budget bands and
energy levels, with plenty free and low-energy; `date_roulette_spins`; filter by
budget/energy/time/car and by both partners' dietary and accessibility needs;
avoid the last few ideas seen; **no AI call at all**; one-tap conversion to a
planner event; cache the library on-device so it spins offline.

**Acceptance criteria**
- **A test asserts no AI call is made** (the fake driver records nothing).
- Free tier, uncapped.
- Empty filter results are handled warmly, offering to loosen the strictest filter — never an empty wheel.
- Spins twice do not repeat immediately.
- Spins with no network.

**Docs to update** `api/app/Modules/DateRoulette/module.md` (gaps closed).

**Size** M.

---

### `[ ]` P6.T09 — Engagement home composition · **S**

**Steps** Compose Home: On This Day → Daily Spark → next up → this week's
challenge. Define the card order and the rules for which cards appear at once
(Home must not become a feed).

**Acceptance criteria**
- Home has **one** primary action.
- At most four cards; order is defined and documented.
- Every core action is ≤3 taps from Home.
- Screen-reader order verified.

**Docs to update** `docs/UX_RULES.md` (rule 14), `mobile/.../accounts/module.md`.

**Size** S.

---

## Phase 7 — AI Recap & Insights

**Goal.** The warm weekly read and the occasional useful nudge — the two places
the AI's tone matters most, and where it can most easily hurt someone.

**Exit criteria.** A recap per couple per week, generated in their local time, in
which private content provably cannot appear; at most one insight a week, never
comparative, suppressed entirely on a safety concern.

**Dependencies.** Phases 2, 4, 5, 6.

---

### `[ ]` P7.T01 — Recap schema and generation · **M**

**Steps** Migrations `recaps` (unique on couple + week), `recap_preferences`;
`weekly_recap` v1 prompt; allow-list (non-private moments, dates, challenges,
revealed spark answers, next week's events); `GenerateWeeklyRecap` idempotent;
`recaps:generate` hourly for couples whose **local** week closed.

**Acceptance criteria**
- One recap per couple per week, idempotent.
- Generated on the couple's Monday, wherever they are.
- **Private content absent from the payload** — one assertion per private source.
- A quiet week produces a **kind, shorter** recap, never a scolding one.

**Docs to update** `api/app/Modules/Recap/module.md`, `docs/AI_PROMPTS.md`.

**Size** M.

---

### `[ ]` P7.T02 — Recap card and history · **S**

**Steps** Home card when fresh; detail; past recaps; `AITag`; regenerate; thumbs
up/down; cached for offline reading.

**Acceptance criteria**
- AI-labelled, regenerable, ratable.
- Regenerate supersedes rather than overwrites.
- Readable offline.

**Docs to update** `mobile/src/features/recap/module.md`.

**Size** S.

---

### `[ ]` P7.T03 — Recap email · **S**

**Steps** Opt-in per user; `SendRecapEmail` idempotent on `email_sent_at`;
on-brand HTML respecting the voice rules.

**Acceptance criteria**
- Opt-in only; sent at most once per recap.
- British spelling; one emoji per sentence; renders in a plain-text client.
- No private content in the email (reuses the payload tests).

**Docs to update** `Recap/module.md` (jobs).

**Size** S.

---

### `[ ]` P7.T04 — Recap safety path · **S**

**Steps** SafetyGuard on inputs and output; on a concern, replace the recap with
the signpost and generate nothing else.

**Acceptance criteria**
- A week containing crisis or abuse content produces **only** the signpost.
- No cheerful summary is ever shown alongside it.
- Nothing about the trigger is stored.

**Docs to update** `Recap/module.md` (AI usage), `docs/AI_PROMPTS.md`.

**Size** S.

---

### `[ ]` P7.T05 — Insights generation · **M**

**Steps** Migration `insights`; `insights` v1 prompt with an **aggregates-only**
allow-list; `insights:generate` weekly after the recap; at most one per couple
per week; **empty is a valid answer**.

**Acceptance criteria**
- At most one insight per couple per week.
- **Silence when there is nothing to say** (tested with a no-pattern fixture).
- **Check-in answers and all private content absent from the payload.**
- Every insight carries exactly one concrete action with a deep link.

**Docs to update** `api/app/Modules/Insights/module.md`, `docs/AI_PROMPTS.md`.

**Size** M.

---

### `[ ]` P7.T06 — Insights tone battery and safety · **M**

**Steps**
1. Fixtures with lopsided activity, sparse data, and crisis signals.
2. Assert output is **never comparative or judgemental** for lopsided fixtures.
3. SafetyGuard suppresses the insight **entirely** on a concern — an upbeat suggestion would be actively harmful.
4. Design what is automatable here versus what needs human review, and write that down.

**Acceptance criteria**
- No lopsided fixture produces a comparative statement.
- A crisis fixture produces no insight, only the signpost.
- The limits of automated tone checking are documented, not pretended away.

**Docs to update** `Insights/module.md` (gaps), `docs/AI_PROMPTS.md`.

**Size** M.

---

### `[ ]` P7.T07 — Insights UI and feedback loop · **S**

**Steps** Single Home card; `AITag`; dismiss (permanent, and de-prioritises that
kind); act (deep link); thumbs up/down into `ai_feedback`.

**Acceptance criteria**
- Dismissal is permanent for that insight.
- The action deep-links correctly into the target module.
- No notification — an insight is found, never an interruption.
- `ai_feedback` is private to the rater.

**Docs to update** `mobile/src/features/insights/module.md`.

**Size** S.

---

## Phase 8 — Monetisation

**Goal.** A couple can pay, both get access, and the server alone decides what
they may use.

**Exit criteria.** RevenueCat webhooks → `couple_entitlements`; trial starts on
second join and cannot be farmed; caps surfaced kindly; **a downgrade never
destroys content**; paywalls honest.

**Dependencies.** Phases 1 and 2 (caps).

---

### `[ ]` P8.T01 — Entitlements schema and the gate · **M**

**Steps** Migrations `couple_entitlements`, `billing_events`
(unique `provider_event_id`), `trials`; `EntitlementService` as the single
answer; `GET /api/entitlements`; middleware/policy gate for paid features.

**Acceptance criteria**
- **The app never decides access** — a modified client gains nothing (tested).
- One active entitlement row per couple.
- The gate is a policy or middleware, never an `if` in a controller.

**Docs to update** `api/app/Modules/Billing/module.md`.

**Size** M.

---

### `[ ]` P8.T02 — RevenueCat webhooks · **M**

**Steps** Signature verification **before** reading the body; acknowledge fast,
process in a queued job; idempotent on `provider_event_id`; reconcile from the
event's own timestamps because **webhook order is not guaranteed**;
`billing:reconcile` daily as a safety net.

**Acceptance criteria**
- A forged body is rejected.
- A replayed webhook changes nothing.
- **Out-of-order webhooks reconcile correctly** (tested with a deliberately shuffled sequence).
- A missed webhook is repaired by reconciliation.

**Docs to update** `Billing/module.md` (jobs, rules).

**Size** M.

---

### `[ ]` P8.T03 — Trial logic · **S**

**Steps** Start on `CoupleLinked`, 7 days, `source = trial`; record
`trials.user_ids` so a user cannot farm trials by re-linking; `trials:expire`
hourly; one honest "ending tomorrow" email.

**Acceptance criteria**
- The trial starts when the **second** partner joins, not at sign-up.
- Re-linking does not grant a second trial (tested).
- Exactly one reminder email, with no countdown pressure.

**Docs to update** `Billing/module.md`, `docs/PRICING.md` § 6.

**Size** S.

---

### `[ ]` P8.T04 — Purchase flow in the app · **M**

**Steps** RevenueCat SDK; after purchase, **refetch `/api/entitlements` and
trust only that** — never the SDK's local result; restore purchases; manage
subscription; offline shows the last known entitlement and fails **closed** for
new paid actions.

**Acceptance criteria**
- Access is granted only after the server confirms.
- Restore works on a reinstall.
- Offline is usable but cannot unlock a new paid action.

**Docs to update** `mobile/src/features/billing/module.md`.

**Size** M.

---

### `[ ]` P8.T05 — Paywall screens · **M**

**Steps** On-brand paywall: what you get, what it costs, how to cancel. Annual
framed as "save £40". Forever positioned as a gift. **No countdown pressure, no
dark patterns.**

**Acceptance criteria**
- Cancellation is explained **on** the paywall.
- One primary action; prices from `config/pricing.php`.
- No fake scarcity, no pre-ticked upsell, no disguised close button.
- Passes the UX review checklist.

**Docs to update** `mobile/.../billing/module.md`, `docs/PRICING.md`.

**Size** M.

---

### `[ ]` P8.T06 — Caps surfaced kindly · **S**

**Steps** Per-feature cap sheets using the real reset date; wire
`GET /api/ai/allowance`; apply the free-tier Moments cap as **read-only**, never
deletion.

**Acceptance criteria**
- Every cap message names the reset date and has no guilt (UX rule 8).
- **Over-cap moments become read-only, never deleted or hidden** — tested.
- The copy is specific, not a generic "upgrade required".

**Docs to update** `Billing/module.md`, `docs/PRICING.md` § 7.

**Size** S.

---

### `[ ]` P8.T07 — Unlink and downgrade behaviour · **M**

**Steps** On `CoupleUnlinked`, the entitlement **follows the payer**; the other
partner drops to Spark at period end and is told **before** confirming the
unlink; no data is ever deleted for a billing reason.

**Acceptance criteria**
- The payer keeps the tier and carries it forward.
- The other partner keeps all their data.
- The unlink confirm states the billing consequence plainly beforehand.

**Docs to update** `Billing/module.md`, `Accounts/module.md` (cross-reference).

**Size** M.

---

### `[ ]` P8.T08 — Store-rules compliance audit · **S**

**Steps** Verify every digital purchase goes through IAP and **nothing** links out
of the app to buy a digital item; add a test that the Stripe order endpoint
**refuses** an `iap`-rail addon key; re-read the current Apple and Google rules
and record the date checked.

**Acceptance criteria**
- A digital add-on cannot be purchased via the Stripe path (tested).
- No in-app link leads to an external digital checkout.
- ADR-011 updated with the date the rules were last read.

**Docs to update** `docs/DECISIONS.md` (ADR-011), `docs/PRICING.md` § 5.

**Size** S.

---

## Phase 9 — More modules

**Goal.** Complete the product surface. These depend on the platform being
right, and two of them hold the most sensitive data in the app.

**Exit criteria.** Love Map feeds the planner; **the gift vault cannot leak**;
bucket list feeds the planner; debrief feeds future suggestions; **check-in
individual answers are unreachable by the partner**.

**Dependencies.** Phases 1–8.

---

### `[ ]` P9.T01 — Love Map fields and entries · **M**

**Steps** Migrations `love_map_fields`, `love_map_entries` (per-entry
`visibility`, defaulting **private**); seed the field library; owner-only write;
partner read of partner-visible entries only; dietary and accessibility entries
**forced** partner-visible, with that stated to the user before they answer.

**Acceptance criteria**
- **The partner cannot write any entry** (tested per field).
- Private entries are absent from the partner endpoint.
- Unset visibility defaults to private.
- Dietary entries reach the planner payload.
- The field library assumes no gender, body type, income or living arrangement.

**Docs to update** `api/app/Modules/LoveMap/module.md` (gaps closed).

**Size** M.

---

### `[ ]` P9.T02 — Love Map UI · **S**

**Steps** Own map (editable, grouped, one question per screen); partner's map
**read-only with no edit affordance at all**; sensitive-field notice; gentle
completeness prompt.

**Acceptance criteria**
- No edit control appears on the partner's map.
- Sensitive fields explain who can see them **before** they are filled in.
- An empty map does not degrade the app.

**Docs to update** `mobile/src/features/love-map/module.md`.

**Size** S.

---

### `[ ]` P9.T03 — Shared wishlist · **S**

**Steps** Migration `wishlist_items`; owner-only edit; either partner may claim;
**the claim is hidden from the item's owner** (field stripped from their
serialisation); URLs stored as text and **never fetched server-side**.

**Acceptance criteria**
- `claimed_by` is absent from the owner's view and present for the claimer (tested).
- Only the wish's owner can edit it.
- No server-side URL fetch exists (no SSRF, no preview leaking what someone is buying).

**Docs to update** `api/app/Modules/Wishlist/module.md`.

**Size** S.

---

### `[ ]` P9.T04 — Gift vault · **M**

> One of the two most privacy-critical tasks in the project.

**Steps** Migration `gift_vault_items`; **author-only for every action, with no
sharing flag and no partner access path**; author-only reminders ahead of key
dates (14-day default); excluded from every shared AI prompt and from the
partner's export; contentless reminder notification.

**Acceptance criteria**
- **The partner gets 403/404 on every gift vault route** — one test per route.
- Vault items are absent from the partner's index, search, **export** and AI payloads.
- Reminders reach only the author, with a contentless body.
- Deleting is immediate and complete; there is no shared trash.

**Docs to update** `Wishlist/module.md` (privacy, tests).

**Size** M.

---

### `[ ]` P9.T05 — Bucket list · **S**

**Steps** Migration `bucket_list_items`; either partner may manage; completion
**offers** a pre-filled Moment; uncomplete leaves the Moment alone; feeds the
`date_plan` allow-list; progress **never framed as a shortfall**; offline create.

**Acceptance criteria**
- Completion never silently creates a Moment.
- Items appear in the date planner payload.
- Progress copy has no deficit framing (UX rule 8).

**Docs to update** `api/app/Modules/BucketList/module.md` (gaps closed).

**Size** S.

---

### `[ ]` P9.T06 — Date debrief · **M**

**Steps** Migrations `date_debriefs`, `date_debrief_responses`; offered once
after a date passes, expiring quietly after 7 days; **two taps minimum**;
**neither response visible until both submit**; reveal **offers** a Moment;
aggregates feed `date_plan` and `insights`; **free-text notes excluded from
shared AI output**; ratings never compared.

**Acceptance criteria**
- The other response is absent from the JSON until both have submitted.
- Two taps suffice (note optional).
- Skipping has no consequence and is never mentioned again.
- Notes are absent from recap and insights payloads.
- No output ever says one partner liked it more.

**Docs to update** `api/app/Modules/DateDebrief/module.md`.

**Size** M.

---

### `[ ]` P9.T07 — Check-in: responses and privacy · **M**

> The other most privacy-critical task. **Build it last within this phase.**

**Steps** Migrations `check_in_responses`, `check_in_preferences`
(`share_trend` default **false**); opt-in, off by default; three one-question
screens, no free text; **owner-only policy with no partner access path at any
setting**; a crisis signpost shown to the submitter **only** — the partner is
never told.

**Acceptance criteria**
- **The partner gets 403/404 on every route that could expose an individual response** — one test per route.
- **No individual value appears in any response body the partner can fetch.**
- A crisis signpost reaches the submitter and **not** the partner.
- The feature is entirely optional and nothing pressures enabling it.
- Check-in values are absent from every AI payload (asserted directly).

**Docs to update** `api/app/Modules/CheckIn/module.md`.

**Size** M.

---

### `[ ]` P9.T08 — Check-in combined trend · **M**

**Steps** `ComputeCheckInTrend` running **only** when both `share_trend` are
true; coarse direction over several weeks, not weekly pairs; **suppressed below
4 weeks** of both partners' data, because with one week and your own answer the
other's is trivially recoverable; either partner opting out removes it
immediately for both; never framed as a score or a diagnosis.

**Acceptance criteria**
- The trend needs both opt-ins and vanishes the moment either opts out.
- **The trend is suppressed below the 4-week minimum** — the de-anonymisation guard, tested.
- The output cannot be inverted to recover an individual weekly answer.
- No score, no label, no assessment of the relationship.

**Docs to update** `CheckIn/module.md` (rules, gaps).

**Size** M.

> **Open question, recorded in the module doc:** whether this feature should
> exist at all. It is the one most likely to be misused as a scorecard. Test it
> with real couples before promoting it.

---

### `[ ]` P9.T09 — Check-in UI · **S**

**Steps** Opt-in inside the **Us tab only** — deliberately **not** on Home,
because a private check-in must not be prompted on a screen a partner may be
looking over; lock badge on every answer screen; own trend; combined trend when
eligible; optional weekly reminder with a neutral body.

**Acceptance criteria**
- No check-in prompt appears on Home.
- Every answer screen shows 🔒 "Only you can see this".
- The reminder body reveals nothing.

**Docs to update** `mobile/src/features/check-in/module.md`.

**Size** S.

---

### `[ ]` P9.T10 — Privacy regression sweep · **M**

**Steps** One consolidated suite asserting, for **every** permanently-private and
hidden-until-reveal field in `docs/ARCHITECTURE.md` § 3.1, that it is absent
from: the partner's index, detail, search, data export, and every AI payload.
Make it the gate that a new private field must extend.

**Acceptance criteria**
- Every row of the privacy-class table has a corresponding test.
- A new private field without a test is caught in review (documented as the rule).
- The suite runs in CI.

**Docs to update** `docs/ARCHITECTURE.md` § 3.1, `docs/MODULES.md` (private-data table).

**Size** M.

---

## Phase 10 — Launch readiness

**Goal.** Something we can honestly put in front of strangers who will trust it
with intimate details of their relationship.

**Exit criteria.** Accessibility audited on device; OWASP API Top 10 reviewed;
**an authorisation test on every endpoint**; legal placeholders in place; EAS
builds on TestFlight and internal testing.

**Dependencies.** Phases 0–9.

---

### `[ ]` P10.T01 — Accessibility audit on real devices · **M**

**Steps** VoiceOver and TalkBack pass over every screen; largest dynamic type;
reduce motion; 44 pt targets; screen-reader order; colour-only checks; reveal
animations announce their result.

**Acceptance criteria**
- Every screen is operable by screen reader end to end.
- Nothing clips at the largest OS text size.
- Every icon button has a label.
- Reduce motion is honoured everywhere.
- Fix every issue found, or record it with a reason.

**Docs to update** `docs/UX_RULES.md` (rule 12), each mobile `module.md`.

**Size** M.

---

### `[ ]` P10.T02 — Authorisation test completeness gate · **M**

**Steps** Enumerate every route (`route:list`); assert every one has a
negative-authorisation test; add a CI check that **fails when a route has no
authorisation test**.

**Acceptance criteria**
- 100% of routes covered.
- A new route without an authorisation test fails CI.

**Docs to update** `docs/ARCHITECTURE.md` § 9.

**Size** M.

---

### `[ ]` P10.T03 — Security review: OWASP API Top 10 · **M**

**Steps** Work the list: broken object-level authorisation (our biggest risk —
every couple-scoped resource), broken authentication, excessive data exposure,
rate limits, broken function-level authorisation, mass assignment, security
misconfiguration, injection, inventory, logging. Add rate limits per endpoint
class. Audit Sentry scrubbing so a stack trace cannot carry a moment's text.

**Acceptance criteria**
- Each item has a written finding and an action.
- Rate limits exist on auth, invite, nudge and AI-triggering endpoints.
- **Sentry carries no personal content** (verified with a deliberate test error).
- No endpoint returns more than its resource declares.

**Docs to update** `docs/ARCHITECTURE.md`, `docs/DECISIONS.md` for anything structural.

**Size** M.

---

### `[ ]` P10.T04 — Performance pass · **M**

**Steps** Fix N+1s (eager loading everywhere), add the indexes the real query
plans want, paginate every list, measure cold start and the Moments feed at
scale, seed a 2,000-moment couple and profile.

**Acceptance criteria**
- No N+1 on any index endpoint (asserted with a query-count test).
- The Moments feed is smooth at 2,000 moments.
- Cold start to interactive Home within an agreed budget (set it, write it down).

**Docs to update** `docs/ARCHITECTURE.md`, module docs where indexes changed.

**Size** M.

---

### `[ ]` P10.T05 — Privacy policy, terms and the data map · **M**

**Steps** Draft placeholders; produce a **data map** (what we hold, why, how
long, who processes it: Anthropic, Resend, RevenueCat, Stripe, S3, Sentry);
document retention and the UK GDPR rights we honour (export, deletion); surface
them in the app.

**Acceptance criteria**
- The data map names every processor and what reaches it.
- Retention periods are stated per data class.
- Export and deletion are reachable in two taps from the Us tab.
- Marked clearly as placeholders pending legal review.

**Docs to update** new `docs/PRIVACY.md`, `docs/ARCHITECTURE.md` § 3.1.

**Size** M.

---

### `[ ]` P10.T06 — Analytics events · **S**

**Steps** Define a minimal event set against the metrics in `docs/PRICING.md`
§ 8 (second-partner join rate, trial conversion, which cap bites, add-on
take-up). **Counts and funnels only — never content.** Document every event.

**Acceptance criteria**
- Every event maps to a metric we said we would watch.
- **No event carries a moment, answer, note or any free text.**
- The event list is documented and reviewable.

**Docs to update** new `docs/ANALYTICS.md`, `docs/PRICING.md` § 8.

**Size** S.

---

### `[ ]` P10.T07 — Final product name decision · **S**

> ⚠️ **Must happen before the first TestFlight build.** Bundle identifiers
> cannot be changed after store release (ADR-001).

**Steps** Decide the real name; update `SWOON_NAME`, `EXPO_PUBLIC_APP_NAME`;
decide whether to rename `config/swoon.php`, the `swoon` deep-link scheme and the
`test.swoon.app` bundle identifiers; secure the domain and the store names.

**Acceptance criteria**
- Bundle identifiers are final.
- The deep-link scheme and universal-link host are final and the association files are hosted.
- ADR-001 updated with the outcome.

**Docs to update** `docs/DECISIONS.md` (ADR-001), `docs/BRAND.md`, `README.md`.

**Size** S.

---

### `[ ]` P10.T08 — Store assets and listings · **M**

**Steps** Icon, splash, screenshots (on-brand, showing the reveal mechanic),
descriptions in the brand voice, keywords, age rating, **privacy nutrition
labels / Data safety form** — which must match the data map from P10.T05.

**Acceptance criteria**
- Both stores' asset requirements met.
- **Privacy declarations match the data map exactly** (a mismatch is a rejection and a trust problem).
- Copy follows the voice rules.

**Docs to update** `docs/BRAND.md`, new `docs/STORE.md`.

**Size** M.

---

### `[ ]` P10.T09 — EAS builds · **M**

**Steps** `eas.json` with development, preview and production profiles;
environment variables per profile; signing credentials; first production builds
for both platforms.

**Acceptance criteria**
- A production build installs and reaches the production API.
- No secret is embedded in the app bundle (**verify by inspecting it** — this is the ANTHROPIC_API_KEY guarantee).
- Build numbers increment automatically.

**Docs to update** `docs/DEV_TOOLING.md`.

**Size** M.

---

### `[ ]` P10.T10 — TestFlight and internal testing · **M**

**Steps** Upload to TestFlight and Play internal testing; recruit **real
couples** (this product cannot be tested solo); collect feedback; triage.

**Acceptance criteria**
- Both tracks are live with external testers.
- At least five real couples through the full critical path.
- Feedback triaged into the plan as new tasks.

**Docs to update** `docs/SESSION_LOG.md`, `docs/PLAN.md` (new tasks from feedback).

**Size** M.

---

### `[ ]` P10.T11 — Pre-launch review of the safety battery · **S**

> The standing action from P2.T04.

**Steps** Have the SafetyGuard battery and the signpost copy reviewed by someone
with relevant expertise; act on the findings; re-check the UK helpline numbers
and URLs are current.

**Acceptance criteria**
- The battery has been reviewed by someone qualified, and that is recorded.
- Signpost numbers and URLs verified correct **on the day**.
- False-positive rate measured on the benign set and judged acceptable.

**Docs to update** `Ai/module.md` (gap closed), `docs/AI_PROMPTS.md`, `docs/DECISIONS.md`.

**Size** S.

---

## Phase 11 — High-ticket add-ons

**Goal.** The revenue that actually matters per couple. Physical and human
deliverables, on the Stripe rail, with real fulfilment behind them.

**Exit criteria.** Year in Moments renders and can be ordered as a book with an
approved proof; concierge runs as a waitlist with a real workflow; date packs
sell through IAP.

**Dependencies.** Phases 4, 7, 8.

---

### `[ ]` P11.T01 — Year in Moments generation · **M**

**Steps** Migration `year_recaps`; `year_in_moments` v1 prompt; allow-list of the
year's **non-private** moments, dates, milestones, challenges; **validate every
returned `moment_id` exists in the input**; SafetyGuard; `year-recaps:generate`
each December.

**Acceptance criteria**
- **Every `moment_id` in the output exists in the input** — the story cannot invent events.
- Private moments absent from the input and not offered for inclusion.
- A sparse year produces something kind and short, not padded.
- A year containing grief is not narrated cheerfully.

**Docs to update** `api/app/Modules/YearInMoments/module.md`, `docs/AI_PROMPTS.md`.

**Size** M.

---

### `[ ]` P11.T02 — Year in Moments story UI and curation · **M**

**Steps** Scrollable chapter view; curation screen to include/exclude moments;
`AITag`; regenerate; cached offline.

**Acceptance criteria**
- **Either partner can include or exclude any moment before anything is printed** — an uneditable auto-generated book would be a disaster.
- Private moments never appear in the curation list.
- Curation changes are reflected in the output.

**Docs to update** `mobile/src/features/year-in-moments/module.md`.

**Size** M.

---

### `[ ]` P11.T03 — Choose a print partner · **S**

**Steps** Evaluate print partners: API, trim sizes, colour profile, lead times,
unit cost, shipping, returns. Decide, and **model the margin against the £59/£89
prices** — the pricing assumes a cost that is not yet quoted.

**Acceptance criteria**
- A partner is chosen with a recorded reason.
- Unit cost and lead time are known; the margin at £59 and £89 is calculated.
- If the margin does not work, `docs/PRICING.md` is corrected rather than the problem ignored.

**Docs to update** `docs/DECISIONS.md` (new ADR), `docs/PRICING.md` § 4, `YearInMoments/module.md` (gaps closed).

**Size** S.

---

### `[ ]` P11.T04 — Book layout, proof and order · **L → split** · **L**

> **Do not implement as one task.** Split into:
> - **P11.T04a** Layout engine (server-side PDF, originals at print resolution) · M
> - **P11.T04b** Proof rendering and the **mandatory approval gate** · M
> - **P11.T04c** Stripe order, fulfilment handoff, order status and emails · M

**Acceptance criteria (for the split set)**
- **A proof must be previewed and explicitly approved before submission** — printing is irreversible.
- Originals are used at print resolution; display sizes stay in the app.
- The book is purchased via **Stripe** (physical good) and **cannot** go through the IAP rail (tested).
- The print partner receives only the approved layout and the shipping details needed to deliver.

**Docs to update** `YearInMoments/module.md`, `Billing/module.md`, `docs/PRICING.md`.

**Size** L — **split before starting.**

---

### `[ ]` P11.T05 — Premium date packs · **M**

**Steps** `date_pack` v1 as an **authoring** tool; human review before sale;
pack entity and seeding; **IAP via RevenueCat** (digital); seasonal availability.

**Acceptance criteria**
- Packs are reviewed by a human before going on sale — never generated live for a buyer.
- Purchased through IAP, never Stripe (tested).
- Entitlement to a pack is server-side.

**Docs to update** `api/app/Modules/Challenges/module.md`, `docs/PRICING.md`, `docs/AI_PROMPTS.md`.

**Size** M.

---

### `[ ]` P11.T06 — Concierge Occasion waitlist · **M**

**Steps** Waitlist capture (not a purchase); a human workflow document for how an
occasion is actually planned and booked; **model whether £149 covers the time**;
explicit consent before any Love Map or Gift Vault data reaches a human
concierge.

**Acceptance criteria**
- Launches as a **waitlist**, not a purchasable product.
- The human workflow is written down before anything is sold.
- Margin at each tier is modelled; if £149 does not cover the work, the price or the scope changes.
- **No private data reaches a concierge without explicit, specific consent.**

**Docs to update** `docs/PRICING.md` § 4, new `api/app/Modules/Concierge/module.md`, `docs/MODULES.md`.

**Size** M.

---

### `[ ]` P11.T07 — Gift Concierge · **M**

**Steps** £49 service fee + gift cost via Stripe; uses Gift Vault and Love Map
**with explicit consent**; fulfilment workflow; reminders before key dates.

**Acceptance criteria**
- **Consent is explicit and specific** before vault contents reach a human.
- Stripe rail only (physical good + human service).
- The recipient partner learns nothing — the whole point is a surprise.

**Docs to update** `Wishlist/module.md`, `docs/PRICING.md`, `docs/MODULES.md`.

**Size** M.

---

## Appendix — things deliberately not built

Recorded so they are not quietly reintroduced:

| Not building | Why |
|---|---|
| **Partner location sharing, "where is my partner", location history** | A relationship app must not become a tool for coercive control. Non-negotiable, at any level of user demand. |
| **Remote push notifications** | Founder constraint (ADR-010). Local + email instead. |
| **AI as therapist or relationship assessor** | Out of scope and potentially harmful. Insights observe and suggest; they never diagnose. |
| **A shared gift vault** | A shared gift vault is not a gift vault. |
| **Partner-visible individual check-in answers** | Would turn an opt-in private check-in into a scorecard. |
| **Streaks that break** | Guilt mechanics (UX rule 8). They pause. |
| **AI in Date Roulette** | It is the zero-cost, instant, free fallback. A model call defeats its only purpose. |
| **A social feed, profiles, or anything public** | This is a private notebook for two people. |
| **Device-calendar sync** | Possibly later; not scoped, and read access to a calendar is a large privacy ask. |
| **Digital purchases via Stripe** | Store rules (ADR-011). |
| **Routing app data through OmniRoute** | Dev tooling only. Would break the single-door AI guarantee. |
