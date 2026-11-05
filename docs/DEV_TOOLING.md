# Dev tooling — Swoon

**Last updated:** 2026-11-05

How to run the project, and the optional tools around it.

---

## 1. Prerequisites

| Tool | Version verified on this machine |
|---|---|
| PHP | 8.5.11 (via Herd) |
| Composer | 2.10.3 |
| Node | 26.12.1 (npm 11.20.0) |
| MySQL | reachable on 127.0.0.1:3306 |
| Python | 3.12.10 (for Graphify) |
| Git | 2.45.1 |
| Laravel Herd | installed (`~/.config/herd`, PHP 8.3 / 8.5 / 8.6 available) |

**PHP is not on the Git Bash `PATH`** on this machine — it resolves in
PowerShell via `~/.config/herd/bin`. Run `php`, `composer` and `artisan` from
PowerShell, or add Herd's bin directory to your Bash `PATH`.

---

## 2. First run

```powershell
# API
cd api
composer install
cp .env.example .env          # then fill in the keys (see § 6)
php artisan key:generate
php artisan migrate

# Mobile
cd ../mobile
npm install
```

Create the databases once (Herd's MySQL):

```sql
CREATE DATABASE swoon      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE swoon_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

> **Note.** The MySQL root account on this machine has a password set, so
> `DB_PASSWORD` in `api/.env` must be filled in before `migrate` will run. The
> test suite defaults to SQLite in-memory and needs neither (ADR-008).

---

## 3. Running it

### API — Herd serves HTTP; it does **not** run the queue or scheduler

Link the site once:

```powershell
cd api
herd link swoon-api          # serves https://swoon-api.test
herd secure swoon-api        # optional: trusted local TLS
```

Then, in a second terminal, run what Herd does not:

```powershell
cd api
composer dev                 # queue:work + schedule:work + pail (log tail)
```

Or individually:

```powershell
php artisan queue:work --tries=3
php artisan schedule:work
php artisan pail
```

**All AI calls run in queued jobs**, so without a queue worker nothing AI-shaped
will ever finish. Reminders, recaps and On This Day need the scheduler.

### Mobile

```powershell
cd mobile
npm start                    # then i / a / w
```

---

## 4. Getting the app to reach the API ⚠️

**Phones and emulators cannot resolve Herd's `.test` domain.** This is the single
most common "nothing works" moment on this stack. The app reads its base URL
from `EXPO_PUBLIC_API_URL` (see `mobile/app.config.ts` → `extra.apiUrl`).

| Target | What to use | Why |
|---|---|---|
| **iOS simulator** | `https://swoon-api.test` (the default) | Shares the Mac's DNS and resolver, so `.test` works. |
| **Android emulator** | `http://10.0.2.2:8000` | `10.0.2.2` is the emulator's alias for the host's loopback. The emulator has its own DNS, so `.test` never resolves. Needs `php artisan serve` on 8000, because this bypasses Herd. |
| **Physical device, same Wi-Fi** | `http://<your-LAN-IP>:8000` | e.g. `http://192.168.1.42:8000`. Run `php artisan serve --host=0.0.0.0 --port=8000`. |
| **Physical device, anywhere** | the `herd share` URL | Herd tunnels the site publicly over HTTPS. Best option for testing deep links and Apple/Google sign-in, which want a real HTTPS origin. |

```powershell
# A public HTTPS URL for the linked site
cd api
herd share
# -> https://something.ngrok-free.app

# Point the app at it
cd ../mobile
$env:EXPO_PUBLIC_API_URL = "https://something.ngrok-free.app"
npm start --clear
```

The Phase 0 Home screen shows the resolved base URL and whether the health check
succeeded, so a misconfiguration is visible immediately rather than appearing as
a blank screen.

> **Android and plain HTTP.** Android blocks cleartext traffic by default. The
> `10.0.2.2` and LAN-IP options above need an `expo-build-properties` opt-in for
> a dev build, or use `herd share` over HTTPS and avoid the issue.

---

## 5. Checks

Run these before calling a task done. CI runs the same commands.

```powershell
# API
cd api
composer check                # pint --test, phpstan, pest
composer lint                 # fix style
composer test                 # tests only
composer analyse              # Larastan level 6

# Mobile
cd mobile
npm run check                 # tsc --noEmit, eslint, prettier --check, jest
npm run lint:fix
npm run format
npm test
```

**Current state:** API 3 tests passing, Larastan level 6 clean, Pint clean.
Mobile 57 tests passing (the contrast gate), typecheck clean, lint clean.

---

## 6. Secrets you must add by hand

Nothing in this repository contains a real secret, and nothing should.

| Variable | Where | Needed for |
|---|---|---|
| `DB_PASSWORD` | `api/.env` | Migrations and local dev |
| `ANTHROPIC_API_KEY` | `api/.env` | Any AI feature (Phase 2+) |
| `RESEND_API_KEY` | `api/.env` | Email; also verify the sending domain in Resend |
| `REVENUECAT_API_KEY`, `REVENUECAT_WEBHOOK_SECRET` | `api/.env` | Subscriptions (Phase 8) |
| `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | `api/.env` | Physical add-ons (Phase 11) |
| `AWS_*` | `api/.env` | S3 media (Phase 4); local dev uses the `local` disk |
| `SENTRY_LARAVEL_DSN` | `api/.env` | Error tracking; leave empty locally |
| `APPLE_*`, `GOOGLE_*` | `api/.env` | Social sign-in (Phase 1) |

Set `AI_DRIVER=fake` to work on AI-adjacent code with no key and no spend.

---

## 7. Graphify

Builds a persistent knowledge graph of the codebase. The session protocol asks
you to read `graphify-out/GRAPH_REPORT.md` to orient **before** grepping.

```powershell
graphify claude install       # once, in the repo root
/graphify .                   # build the graph
/graphify . --update          # refresh after structural changes
```

* `graphify-out/cache/` is **gitignored**.
* The rest of `graphify-out/` **is committed**, so a new session can read the
  report without building it first (ADR-014).
* Refresh it after any structural change — a stale graph is worse than none,
  because it gets trusted.

> The installed CLI is 0.9.97 while the bundled skill is 0.9.102. `graphify
> install` would downgrade the skill, so upgrade the package instead
> (`pip install -U graphifyy`) if the mismatch causes trouble.

---

## 8. Claude Code plugins

| Plugin | Status |
|---|---|
| **Ponytail** | ✅ active — `ponytail@ponytail` v5.3.3. Minimalism: reuse before writing, prefer framework features, no speculative abstractions. |
| **Agent Skills** | ✅ active — `agent-skills@addy-agent-skills` v0.6.15. 25 skills, 9 slash commands, 4 subagents covering spec → plan → build → verify → review → ship. |

Both are installed at **user scope**, so they are not automatic on a fresh
clone. A new teammate runs:

```powershell
claude plugin install agent-skills@addy-agent-skills
claude plugin install ponytail@ponytail
```

> **Better, not yet done:** declare `addy-agent-skills` in the project's
> `.claude/settings.json` under `extraKnownMarketplaces`, which is exactly what
> that key is for — teammates then get the source without adding it by hand.
> See ADR-013.

Skills load at session start, so a freshly installed plugin appears in the
**next** session.

### The Agent Skills worth knowing

Relevant to the session protocol's spec → plan → build → test → review step:

| Skill / command | Use |
|---|---|
| `spec-driven-development`, `/spec` | Turn a PLAN.md task into a precise spec before coding |
| `planning-and-task-breakdown`, `/planning` | Split an L task (PLAN.md forbids starting one unsplit) |
| `test-driven-development`, `/test` | The default for this project — build with tests |
| `code-review-and-quality`, `/review` | Pre-commit review pass |
| `security-and-hardening` | Phase 10's OWASP API Top 10 review |
| `api-and-interface-design` | Designing each module's endpoint table |
| `documentation-and-adrs` | Keeping `module.md` and `DECISIONS.md` honest |
| `shipping-and-launch`, `/ship` | Phase 10 launch readiness |
| `observability-and-instrumentation` | Sentry and the analytics events in P10.T06 |
| `code-simplification`, `/code-simplify` | Pairs with Ponytail |

Subagents: `code-reviewer`, `security-auditor`, `test-engineer`,
`web-performance-auditor`.

Use them where they help — they do not override anything in `CLAUDE.md`, and
Ponytail's minimalism still wins on any conflict.

---

## 9. OmniRoute

**Local dev gateway only. Not installed into this repository, and it must stay
that way.**

OmniRoute is an optional local gateway for inspecting and routing model traffic
during development. If you use it, run it on your own machine, outside the repo,
and point only your own tooling at it.

**The application's runtime calls Anthropic directly.** Never route user data
from the app through OmniRoute:

* It would put intimate personal data through a dev-grade hop.
* It is not covered by our data-processing posture or anything we tell users.
* It would break the one guarantee the AI architecture rests on — that the only
  path from a couple's data to a model is `AiService`, with its privacy filter,
  SafetyGuard and cost log.

There is no configuration in this repository for it, by design.

---

## 10. Hooks and pre-commit

| Hook | What it does |
|---|---|
| `.claude/hooks/check-module-docs.sh` | **Stop** hook. Warns if files under `api/app/Modules/<X>/` or `mobile/src/features/<x>/` changed without that module's `module.md`. A warning, not a block. |
| `.githooks/pre-commit` | Runs Pint and ESLint on **staged files only**, so it stays fast. |

Enable the git hook once per clone:

```powershell
git config core.hooksPath .githooks
```

---

## 11. CI

`.github/workflows/ci.yml`:

| Job | Runs |
|---|---|
| `api` | Pint `--test`, Larastan level 6, Pest (SQLite) |
| `test-mysql` | The same Pest suite against **MySQL 8** (ADR-008) |
| `mobile` | `tsc --noEmit`, ESLint, Prettier check, Jest |

The mobile job includes the contrast gate, so **a palette change that breaks
WCAG AA fails the build**.
