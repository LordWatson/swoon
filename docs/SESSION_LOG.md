# Session log — Swoon

One short entry per working session. **Newest first.**

Format: date · task ID(s) · what changed · anything left over.

---

## 2026-11-05 — Phase 2 in progress · AI platform (P2.T01 …)

**P2.T01 — the driver seam and the fake.** One interface between the
application and Claude, two implementations, chosen by `config('ai.driver')`.
195 API tests.

* **Model ids are gated, not promised.** `AiRequest` resolves its model, effort
  and token ceiling from `config('ai.tasks')` in its constructor, so a driver
  asks the request and the request asks config. `ModelIdsTest` then scans every
  PHP file in the project and fails on a model id anywhere but `config/ai.php`.
  Proven to bite by pasting one in on purpose.
* **"No test makes a real AI call" needed two belts.** `AI_DRIVER=fake` means
  the Anthropic driver is never constructed — asserted through
  `app()->resolved()`, not assumed. The second belt matters more than it looks:
  `phpunit.xml` used to set a placeholder `ANTHROPIC_API_KEY`, and because
  `APP_ENV=testing` with no `.env.testing` loads the developer's own `api/.env`
  (which holds a working key), a placeholder would have let an accidental call
  go out and come back 401 — a far quieter failure than "no key, refusing".
  It is force-blanked now, and a test asserts the real driver refuses.
* **Kept both fakes, for a reason about defaults rather than features**
  (ADR-024). laravel/ai's `Agent::fake()` is good but opt-in per test and keyed
  on the agent class; ours is the suite default and records per task. laravel/ai's
  earns its place covering `AnthropicDriver`'s own mapping, which our fake
  replaces and so could never test.
* **laravel/ai merges its config into our `config/ai.php`** under the same `ai`
  key — `providers`, `default`, `caching`, `conversations`, none of which
  collide with ours. So the Anthropic key already had a home at
  `config('ai.providers.anthropic.key')`, and the duplicate we had written is
  gone. One secret, one path.
* **laravel/ai is agent-class-shaped; Swoon is task-shaped.** Its model and
  token ceiling are PHP attributes, which are compile-time constants and cannot
  read config. One `TaskAgent` carries the per-task settings as **provider
  options** instead, which the gateway merges over the body it built — merging
  `output_config` key by key, so setting `effort` leaves the structured-output
  format intact. No `thinking` key: these models reject an explicit
  `budget_tokens` with a 400.
* **A schema is required on every request.** Without one laravel/ai sends no
  `output_config`, the model answers in free text, and `SchemaValidator` has
  nothing to check — a silent hole in the one rule that stands between the
  model and a couple. `AiRequest` demands a schema and `TaskAgent` refuses an
  empty one.

**P2.T02 — prompts as pinned files.** `resources/prompts/<name>/<version>.md`,
with the live version in `config('ai.prompts.active')`. 209 API tests.

* **The config was pinning four prompts that had no files.** Trimmed to what
  exists, with a test that fails on a pin without a file — the alternative is a
  promise that only breaks when a couple triggers the feature. Each phase now
  adds its pin alongside the file it writes.
* **The registry is strict in both directions.** A slot with no value throws,
  and so does a value with no slot: a task's allow-list names exactly what it
  may see, so a value the prompt never asks for means the allow-list and the
  prompt have drifted and nobody can tell which is right. `null` is rejected
  outright — decide what absence should say and pass that.
* **`false` renders as "no", not as nothing.** PHP would make it an empty
  string, which is precisely the empty slot the class exists to prevent.
* **Values are filled in one pass**, so `{{ nickname }}` typed inside a love
  note stays text rather than reaching another variable. Nothing escapes the
  values otherwise, because escaping prose for a language model is theatre; the
  defences are the output schema and SafetyGuard.
* **A backslash vanished out of a bash heredoc**, turning `'/\'` into `'/'`
  and making the file unparseable with an error pointing twenty lines away.
  Paths are concatenated with forward slashes now. Worth remembering: lint the
  file, do not trust the reported line.

**P2.T03 — output validated against the schema that was sent.** 238 API tests.

* **One declaration, two jobs.** A task's shape lives in `TaskSchemas`;
  `AiRequest::jsonSchema()` turns it into `output_config.format` and
  `SchemaValidator` checks the reply against the same array. A second
  description of the same shape is the thing that drifts.
* **Exactly one retry, with the same prompt.** A repair turn naming what the
  model got wrong would fix more replies and ruin the record — the prompt that
  produced the output would no longer be the pinned version
  `ai_generations` points at. Proven by queueing three responses and asserting
  the third was never asked for.
* **`Generation::$structured` is null unless the reply passed**, so "malformed
  output never reaches a couple" is a property of the type rather than a rule
  somebody remembers. Every attempt is kept, because every attempt was billed.
* **The validator throws on a keyword it does not implement** rather than
  skipping it, and the suite walks every declared schema to catch that at
  build time. Proven to bite by adding `format` on purpose.
* **`get_debug_type()` calls a list and an object both "array"**, which made a
  type mismatch read "should be array, not array". It says "object" now.
* **Two Windows-shaped lessons.** `end()` cannot touch a readonly property
  (it takes the array by reference), and PowerShell's `Set-Content -Encoding
  utf8` writes a BOM, which makes `declare(strict_types=1)` "not the very
  first statement". Python with `newline=''` for file edits from now on.

**Left over:** the founder's real Anthropic key sits in `api/.env` (untracked,
git-ignored) and was read aloud during this session while checking the test
environment — worth rotating.

## 2026-11-05 — Phase 1 complete · Accounts & Couples (P1.T01 … P1.T10)

Ten tasks, ten commits. Two people can now sign up, link into a couple, and
leave cleanly. Sixteen endpoints, 161 API tests and 136 mobile tests.

**Order changed.** T08–T10 were taken before T05–T07: those three are mobile,
and finishing the backend first let T10's authorisation sweep cover every
endpoint in one pass. T07 (the shell) then came before T05 and T06, because
both navigate into it and building them first would have meant guessing.

**The things worth remembering**

* **The one-couple invariant is a unique index on a virtual generated column.**
  MySQL has no partial index, and `unique(user_id, left_at)` does not work
  because a UNIQUE index treats NULLs as distinct. Three details each cost a
  failed migration: `CASE` not `IF()` so SQLite accepts it; VIRTUAL not STORED,
  because MySQL refuses `ON DELETE CASCADE` on a base column of a stored
  generated column (error 1215); and declared in `CREATE TABLE`, because SQLite
  cannot add one by `ALTER`. Proven by a test that inserts straight into the
  table.
* **Concurrency guards are conditional writes, not checks.** Two accepts of one
  code both pass every up-front check; `update(... where accepted_at is null)`
  succeeds once.
* **Sign-in refuses identically and in the same time.** A wrong password and an
  unknown address give the same 422, and the Action hashes against a dummy
  rather than returning early — a login answering in 5 ms for unknown addresses
  and 90 ms for known ones is an enumeration oracle whatever the message says.
* **The email rate limit is keyed on email+IP.** Keyed on the address alone, a
  stranger could lock a real person out of their own account with five failed
  attempts.
* **Social tokens: algorithm pinned, audience matched, no client id means
  refuse everything.** Reading the algorithm from the token's own header is the
  alg-confusion bug. ADR-022 for `firebase/php-jwt`; ADR-023 for refusing to
  link a social identity to an existing email account without explicit
  confirmation, because an email match is not proof of being the same person.
* **Deletion leaves a tombstone.** The row survives, emptied, because the other
  partner's shared content has to keep resolving — a couple's memories are two
  people's memories. The purge job refuses to touch an account that is not
  soft-deleted, which has its own test: a stale retry scrubbing a live account
  would be the worst bug in the system.
* **The export JSON shape is settled**, closing a gap open since P0.T07. The
  partner appears as a name and a colour, asserted against the bytes.
* **The authorisation sweep reads the real route table** and fails naming any
  route with no recorded decision. Verified by adding a route and watching it
  fail. It then did its job for real in T06, refusing to pass until
  `PATCH /api/me` and the nudge endpoint had decisions.

**Five bugs the tests caught, four of them mine**

1. `apple_sub` was silently dropped on create, because it is not fillable.
   That default is right, so the Action sets it with `forceFill` — a stray
   `User::create($request->all())` must never hand someone another person's
   identity.
2. My social-auth rejection messages were distinguishable, so the endpoint
   leaked which check had failed. One message to the caller, the reason to the
   log.
3. `SwoonMailable` lost its constructor in the audit fixes, so `InviteMail`
   calling `parent::__construct()` was fatal.
4. `CoupleInviteFactory::accepted()` called `getKey()` on a Factory.
5. Classifying the couple routes as `owned` in the sweep produced a failing
   test that was my classification being wrong, not the code: they take no id,
   so an outsider cannot aim them anywhere. They are `self`.

**Four pieces of module wiring**, each of which every later module needs:
`Factory::guessFactoryNamesUsing` (Laravel looks for a module model's factory
in `Database\Factories\Modules\…`); an explicit `Gate::policy` for `User`,
whose convention points at `App\Policies`; module Artisan command registration
(discovery covers `app/Console/Commands` only, so `invites:remind` would have
failed from the scheduler at 3am); and module event discovery — scoped by glob
to `app/Modules/*/Listeners`, because scanning the tree wholesale registered
every **Action** as a listener for its own first parameter's type.

**Dependencies added:** `firebase/php-jwt` (ADR-022) and `expo-secure-store`
(already mandated by CLAUDE.md). `expo-localization` was *not* added —
`Intl.DateTimeFormat().resolvedOptions().timeZone` is the same answer from the
platform.

**Not verified, and it matters**

* **No mobile code has run on a device.** No JVM, no Android SDK, no emulator
  (P0.T12). Every mobile claim rests on 136 Jest tests and `tsc`.
* **No live Apple or Google sign-in.** `APPLE_CLIENT_ID` and the three
  `GOOGLE_CLIENT_ID_*` values are unset, and until they are both endpoints
  correctly refuse every token.
* **Universal links are not live.** `link.swoon.test` is not a real domain and
  the Android fingerprint needs a release keystore. Paths and file shapes are
  in `docs/DEV_TOOLING.md` § 4.1; the custom scheme works now.
* **Two screens are specified but not built:** the unlink confirm and the
  Privacy & data screen. Their required copy — including the billing
  consequence of an unlink — is written into `Accounts/module.md` so it cannot
  be lost.

**Next:** **Phase 2** — the AI platform. `config/ai.php` was hardened during
the audit (ADR-016, ADR-017), so the cost and safety rules are already written
down and waiting.

---

## 2026-11-05 — Design-language uplift (founder-directed, no task ID)

The founder asked for a major UI/UX and branding lift: "currently it's very
plain and minimal — I want it to feel high ticket, and exciting and engaging."

**The diagnosis.** The brand spec in `BRAND.md` was already good. The
*implementation* was not delivering it. Everything sat on one plane — one
surface colour, one 1px decorative border, one shadow — so nothing could lead.
The largest type was 32pt with no tracking anywhere. Press states were
`opacity: 0.85`, which is the single clearest tell of a cheap-feeling app.
Padding was 16 everywhere. And despite a full set of motion tokens, **nothing
in the app animated at all**.

**Direction chosen** (from three offered): warm editorial, with a deep plum
feature plane inside the cream app. Rejected: dark-first "jewel box", which
fights the "warm and a bit silly" personality and would need every text pair
re-derived; and gradient-glass, which degrades to flat wherever
`expo-glass-effect` is unavailable and so means maintaining two designs
(ADR-021).

**Built**

* **Three planes** — cream page, paper card, plum feature; at most one feature
  per screen. Six new tokens, solved against WCAG *before* being written down:
  `feature`, `featureGradientFrom`, `onFeature`, `featureMuted`,
  `featureAccent`, `primaryGradientFrom`. `feature` is Midnight Plum, the same
  value as `ink`, so the plane inherited a 15.04:1 pair the palette already had.
* **The editorial type pair** — `hero` at 44pt and a `overline` at 12pt, and
  `letterSpacing` on *every* token: negative for display, positive for small
  labels. Tracking scales with size, or a large accessibility setting leaves the
  hero looking spaced out. The overline is **tracked, never uppercased** —
  uppercase is the obvious move and § 2 forbids it.
* **Five elevation steps** instead of one, including a Rosé-tinted glow under
  the primary CTA. A coloured button casting a grey shadow is the giveaway that
  a design system was never looked at on a real screen.
* **Gradients** on the CTA and the feature plane, via `react-native-svg` (already
  installed for Phosphor) rather than adding `expo-linear-gradient`.
* **Press feedback that is felt** — `usePressFeedback()` owns the spring, the
  scale (0.96 button / 0.99 card) and the haptic, so it is one decision rather
  than eight, and reduce-motion is honoured once. `expo-haptics` added, the
  first new dependency since P0.T02 (ADR-019).
* **Two new components**, `Hero` and `Stat`, plus `tone` on `Card` and
  `AvatarPair`, bigger padding on everything, a Fraunces `EmptyState`, and a
  `ProgressBar` whose fill now glides. Home rebuilt around the new layout.
* **`mobile/src/components/README.md`** — rule 15 has required a component
  library README since P0.T04 and there was never one. Ten components documented.

**Two decisions worth knowing**

1. **Reanimated is not used for interaction.** Mutating a shared value in an
   event handler is its documented API, but the React Compiler's
   `react-hooks/immutability` rule cannot tell a `SharedValue` from state, so it
   errors. The choice was a lint suppression in the one hook every control
   depends on, or a different API — so press and progress use React Native's own
   `Animated` (native-driven for the transform), and Reanimated stays for the
   set pieces that need worklets: the reveal, confetti (ADR-020).
2. **`AbortSignal.timeout`-style APIs and Reanimated jest mocks were both
   avoided for the same reason** — things that pass in Node and fail on a
   device, or that need test scaffolding to prove nothing.

**Gate** The contrast test grew to cover the new tokens *and* a new class of
check: **text on a gradient must clear AA at both ends**, since a gradient has
no single background colour and the lighter end is always the binding one. 98
mobile tests pass (73 contrast, 12 design-language, 9 API client, 4 press
feedback); tsc, ESLint and Prettier clean.

**Three tests worth their keep.** The hero is asserted to be the *only*
hero-sized type, so the one-moment-per-screen rule cannot rot. The overline is
asserted to have no `textTransform`. And a press is asserted still to respond
under reduce motion — removing the feedback entirely would be worse than the
animation.

**Preview** The founder cannot run the app (no JVM, no Android SDK, no
emulator — see P0.T12), so the work would have been unreviewable. A canvas with
before / after / the design language is at
<https://claude.ai/artifact/Ae3tAXkjE3BU2qhEKyizqh> — private until shared.

**Not done** Dark mode has not been looked at on a device, only computed: every
dark pair passes the gate, but the feature plane's lift against the plum page is
a 1.25:1 difference that wants a real screen to judge. `Sheet` was left alone.
The `reveal` and confetti set pieces are Phase 6–7 work and are still only
tokens.

**Next:** **P1.T01** — users, couples and the one-couple invariant.

---

## 2026-11-05 — P0.T12 · Queue, scheduler, Maestro harness — **Phase 0 complete**

**Built**

* `App\Jobs\PingJob` and `artisan swoon:queue-test`. The command dispatches one
  ping and then says whether anything collected it, which is the point: with no
  worker running a dispatch succeeds silently and the job just waits, and that
  is indistinguishable from a working setup from the outside. It waits up to
  three seconds before concluding — a worker polls rather than being pushed to,
  and the first version reported "no worker" while a worker was demonstrably
  running.
* `maestro/smoke.yaml`. Asserts what Phase 0 built — the app launches, the theme
  renders, the device can reach the API — and carries the four real
  critical-path steps as TODOs naming the task that fills each one in, in
  dependency order. Added `testID="health-card"` to the Home health card so the
  flow does not depend on copy.
* Two tests on the command's contract: one ping dispatched, and the log line
  carries the token the command printed. The docs tell you to grep for that
  token, so it is asserted rather than trusted.

**`composer dev` had never worked on this machine.** Pail needs the `pcntl`
extension, which Windows PHP does not have, and it was the third process under
`concurrently --kill-others` — so Pail exited 1 immediately and took the queue
worker and the scheduler down with it, every single run. P0.T01's acceptance
criterion saying otherwise was never run to completion. `composer dev` is now
queue + scheduler; the log tail is `composer logs`, with the Windows equivalent
documented beside it (ADR-018). The plan's P0.T01 criterion is amended in place.

**Verified** With the fixed loop: `PingJob` picked up and logged under
`composer dev` (`RUNNING` → `DONE` in 21.97ms, `swoon:ping handled [IP7VJI]` in
the log). A one-minute `Schedule::call()` heartbeat fired on its own at
13:16:00 under `schedule:work`, then removed as the task requires — there are
deliberately no scheduled tasks until the features in ARCHITECTURE § 5 arrive.
`composer check` clean (14 tests / 35 assertions), green on MySQL 8 too,
`npm run check` clean (66 tests).

**The one criterion not met.** `maestro test maestro/smoke.yaml` has never been
run. This machine has no JVM, no Maestro, no Android SDK and no emulator;
installing that stack is a multi-gigabyte decision for the founder, not a side
effect of this task. The flow is the skeleton the task asked for and it parses
as valid YAML, but "it passes" is unproven. `docs/DEV_TOOLING.md` § 5 lists the
five prerequisites in order, with the API-reachability one called out because it
is the one that will actually bite.

**Phase 0 is complete — 12/12.** Both halves boot, the app talks to the API, the
theme and component library are AA-gated, MySQL is migrated, email works through
Resend, and the queue and scheduler are proven. CI now actually runs (it did
not until earlier today).

**Next:** **P1.T01** — users, couples and the one-couple invariant, enforced in
MySQL with a generated column and a unique index, not just in PHP. The gate to
every feature in the app.

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
