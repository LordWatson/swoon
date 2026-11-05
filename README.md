# Swoon 💞

A relationship concierge app for couples. Two people link into a shared space,
and everything in it — dates, moments, plans, challenges — belongs to the couple,
with some things private to one partner.

*"Swoon" is a working name. See [ADR-001](docs/DECISIONS.md).*

> **Status: scaffolding complete, no features built yet.** This repository has
> the project set up, the brand and theme implemented, 21 modules documented, and
> a detailed build plan. Feature work starts at **Phase 1** of
> [`docs/PLAN.md`](docs/PLAN.md).

---

## Quick start

**You need:** PHP 8.5 (via [Laravel Herd](https://herd.laravel.com)), Composer,
Node LTS, MySQL 8, Python 3 (for Graphify).

```powershell
# 1. API
cd api
composer install
cp .env.example .env
php artisan key:generate
# Add DB_PASSWORD to .env, then:
php artisan migrate

# 2. Serve it (Herd)
herd link swoon-api          # -> https://swoon-api.test
herd secure swoon-api        # optional local TLS

# 3. Herd does NOT run the queue or scheduler. In a second terminal:
composer dev                 # queue:work + schedule:work + log tail

# 4. Mobile
cd ../mobile
npm install
npm start                    # then press i / a / w
```

Create the databases once:

```sql
CREATE DATABASE swoon      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE swoon_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### ⚠️ Getting the app to reach the API

**Phones and emulators cannot resolve Herd's `.test` domain.** This is the most
common first stumble.

| Target | Use |
|---|---|
| iOS simulator | `https://swoon-api.test` (the default) |
| Android emulator | `http://10.0.2.2:8000` + `php artisan serve` |
| Physical device (same Wi-Fi) | `http://<LAN-IP>:8000` + `php artisan serve --host=0.0.0.0` |
| Physical device (anywhere) | the `herd share` URL — best for deep links and social sign-in |

```powershell
$env:EXPO_PUBLIC_API_URL = "https://your-tunnel.ngrok-free.app"
npm start --clear
```

The Home screen shows the resolved URL and whether the health check succeeded,
so a misconfiguration is visible rather than a blank screen. Full detail:
[`docs/DEV_TOOLING.md`](docs/DEV_TOOLING.md) § 4.

---

## Checks

```powershell
cd api     && composer check   # Pint, Larastan 6, Pest
cd mobile  && npm run check    # tsc, ESLint, Prettier, Jest
```

Both are green as of 2026-11-05: **3 API tests**, **57 mobile tests** (the WCAG
contrast gate), Larastan level 6 clean.

---

## Layout

```
├── CLAUDE.md               Standing rules for Claude — read this first
├── api/                    Laravel 13, API-only (JSON)
│   └── app/Modules/<M>/    One folder per module, each with module.md
├── mobile/                 Expo app
│   ├── src/theme/          The ONLY place colours and fonts live
│   ├── src/components/     Shared component library
│   └── src/features/<m>/   One folder per module, each with module.md
├── docs/
│   ├── PLAN.md             ⭐ The master build plan — 107 tasks, 12 phases
│   ├── ARCHITECTURE.md     System overview, ERD, AI pipeline
│   ├── BRAND.md            Palette, type, motion, voice
│   ├── UX_RULES.md         15 rules applying to every screen
│   ├── PRICING.md          Tiers, add-ons, entitlements, metrics
│   ├── AI_PROMPTS.md       Every prompt, its version and schema
│   ├── MODULES.md          Index of all 21 modules
│   ├── DECISIONS.md        ADR log, newest first
│   ├── SESSION_LOG.md      One entry per working session
│   └── DEV_TOOLING.md      Herd, Graphify, plugins, CI
└── .claude/                Hooks and settings
```

---

## Documentation

**Documentation is part of the definition of done.** Every module has a
`module.md` and it is updated in the same change as the code.

Start with **[`CLAUDE.md`](CLAUDE.md)** for the standing rules, then
**[`docs/PLAN.md`](docs/PLAN.md)** for what to build next.

---

## Things this app will never do

* **Track a partner's location.** No "where is my partner", no location history,
  no coordinates. A relationship app must not become a tool for coercive control.
* **Leak what is private.** The gift vault, individual check-in answers, locked
  love notes and private moments are never visible to the partner, and never
  reach AI output.
* **Act as a therapist.** The AI is a playful assistant. If something suggests
  abuse, crisis or harm, it gives a short, kind signpost to UK support services
  and nothing else.
* **Trap anyone.** Unlinking needs no consent from the other partner. Deletion is
  immediate and complete. Export always works.

---

## Tech

Laravel 13 · PHP 8.5 · MySQL 8 · Sanctum · Pest · Pint · Larastan 6 ·
Expo · React Native · Expo Router · TypeScript strict · TanStack Query ·
Jest + RNTL · Maestro · Claude via `laravel/ai` · Resend · RevenueCat · Stripe ·
S3 · Sentry
