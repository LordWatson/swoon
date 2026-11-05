# Graph Report - Smitten  (2026-11-05)

## Corpus Check
- 161 files · ~86,049 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 20, .example 1, .neon 1)

## Summary
- 1382 nodes · 1917 edges · 144 communities (90 shown, 54 thin omitted)
- Extraction: 87% EXTRACTED · 13% INFERRED · 0% AMBIGUOUS · INFERRED: 246 edges (avg confidence: 0.95)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `fab08e81`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- with
- App Root & Theme Provider
- Card
- User
- Database Migrations
- package.json
- API HTTP Client
- Service Providers & Routing
- _layout.tsx
- Mobile Dev Dependencies
- Daily Spark 🔥
- Composer Scripts
- Mobile NPM Scripts
- ci.yml
- ESLint Configuration
- PHP Dev Dependencies
- Composer Package Metadata
- PHP Runtime Dependencies
- TypeScript Configuration
- Laravel Bootstrap
- Composer Script Descriptions
- Composer Plugin Config
- Pest Test Harness
- PHP Autoload Mapping
- Jest Configuration
- Expo App Config
- Love Map / Profiles 🗺️
- Laravel Package Discovery
- Module Docs Hook
- Base Controller
- Milestones & Countdowns ⏳
- Moments 📸
- On This Day 🕰️
- Shared Planner 🗓️
- On This Day 🕰️ - mobile
- Wishlist & Gift Vault 🎁 - mobile
- Weekly Recap (AI) ✨
- Wishlist & Gift Vault 🎁
- Year in Moments 📖
- Brand — Swoon 💞
- UX rules — Swoon
- Architecture — Swoon
- Decisions — Swoon
- Dev tooling — Swoon
- AI Platform ✨
- AI prompts — Swoon
- <Module name> <emoji>
- Reminders ⏰
- Billing & Entitlements 🧾
- Bucket List 🪣
- Romantic Challenges 💌
- Check-in (Temperature Check) 🌡️
- Date Debrief 💬
- AI Date Planner 🍷
- Date Roulette 🎡
- Insights (AI) 💡
- Love Notes 💕
- Phase 10 — Launch readiness
- Accounts & Couple Linking 💞
- Phase 9 — More modules
- Pricing — Swoon
- test-mysql
- Accounts & Couple Linking 💞 - mobile
- CLAUDE.md — Swoon
- Phase 2 — AI platform
- Phase 3 — AI Date Planner
- Phase 4 — Moments & On This Day
- Phase 6 — Engagement
- Billing & Entitlements 🧾 - mobile
- Love Notes 💕 - mobile
- Phase 1 — Accounts & Couples
- Phase 8 — Monetisation
- UserFactory.php
- api/README.md
- Phase 11 — High-ticket add-ons
- Phase 7 — AI Recap & Insights
- steps
- env
- Insights (AI) 💡 - mobile
- Swoon 💞
- Illuminate\Http\JsonResponse
- Build plan — Swoon 💞
- Phase 5 — Planner & Milestones
- Welcome to your Expo app 👋
- Moments 📸 - mobile
- Romantic Challenges 💌 - mobile
- Weekly Recap (AI) ✨ - mobile
- Date Debrief 💬 - mobile
- Year in Moments 📖 - mobile
- Bucket List 🪣 - mobile
- TestPingMail
- DatabaseSeeder
- Modules — Swoon
- Love Map / Profiles 🗺️ - mobile
- Session log — Swoon
- docs
- on
- expo-image@~58.0.18
- expo-splash-screen@~58.0.13
- expo-symbols@~58.0.4
- expo-system-ui@~58.0.7
- @expo/ui@~58.0.27
- react-native-gesture-handler@~3.3.0
- react-native-web@~0.21.4

## God Nodes (most connected - your core abstractions)
1. `Card()` - 51 edges
2. `Button()` - 47 edges
3. `EmptyState()` - 34 edges
4. `Sheet()` - 32 edges
5. `useTheme()` - 29 edges
6. `Chip()` - 28 edges
7. `AvatarPair()` - 26 edges
8. `Decisions — Swoon` - 23 edges
9. `AITag()` - 22 edges
10. `ProgressBar()` - 22 edges

## Surprising Connections (you probably didn't know these)
- ``[x]` P0.T10 — Connect MySQL and run the first migration · **S**` --references--> `User`  [INFERRED]
  docs/PLAN.md → api/app/Models/User.php
- `2026-11-05 — P0.T10 · MySQL connected, `users` schema live` --references--> `User`  [INFERRED]
  docs/SESSION_LOG.md → api/app/Models/User.php
- `Mobile` --references--> `AITag()`  [INFERRED]
  api/app/Modules/Ai/module.md → mobile/src/components/AITag.tsx
- `8. AI presentation` --references--> `AITag()`  [INFERRED]
  docs/BRAND.md → mobile/src/components/AITag.tsx
- ``[ ]` P11.T02 — Year in Moments story UI and curation · **M**` --references--> `AITag()`  [INFERRED]
  docs/PLAN.md → mobile/src/components/AITag.tsx

## Import Cycles
- None detected.

## Communities (144 total, 54 thin omitted)

### Community 0 - "with"
Cohesion: 0.17
Nodes (12): with, cache, cache-dependency-path, coverage, extensions, fetch-depth, key, node-version (+4 more)

### Community 1 - "App Root & Theme Provider"
Cohesion: 0.05
Nodes (59): ADR-0019, @testing-library/react-native@^14.1.0, flatten(), renderThemed(), styleOf(), ColourTokens, darkColours, lightColours (+51 more)

### Community 2 - "Card"
Cohesion: 0.08
Nodes (83): Mobile, Mobile, Mobile, Mobile, Mobile, Mobile, Mobile, Mobile (+75 more)

### Community 3 - "User"
Cohesion: 0.21
Nodes (3): User, Changelog, Data model

### Community 4 - "Database Migrations"
Cohesion: 0.14
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+1 more)

### Community 5 - "package.json"
Cohesion: 0.07
Nodes (29): dependencies, expo-constants@~58.0.15, expo-device@~58.0.5, expo-font@~58.0.11, expo-glass-effect@~58.0.5, expo-haptics@~58.0.7, expo-linking@~58.0.16, expo-web-browser@~58.0.7 (+21 more)

### Community 6 - "API HTTP Client"
Cohesion: 0.12
Nodes (17): `[x]` P0.T06 — Health endpoint and typed API client · **S**, 2026-11-05 — Review and audit fixes (no task ID), ApiError, apiRequest(), fetchHealth(), HealthResponse, isRecord(), readToken() (+9 more)

### Community 7 - "Service Providers & Routing"
Cohesion: 0.21
Nodes (5): AppServiceProvider, ModuleServiceProvider, Coding conventions, ADR-009 — Light modular monolith, no modules package, `[x]` P0.T01 — Monorepo and Laravel 13 API · **M**

### Community 8 - "_layout.tsx"
Cohesion: 0.13
Nodes (15): @expo-google-fonts/fraunces@^0.4.1, @expo-google-fonts/nunito@^0.4.2, expo-router@~58.0.30, expo-status-bar@~58.0.4, react-native-safe-area-context@~5.11.1, @tanstack/react-query@^5.106.1, queryClient, RootLayout() (+7 more)

### Community 9 - "Mobile Dev Dependencies"
Cohesion: 0.14
Nodes (19): devDependencies, eslint-config-prettier@^10.1.8, jest@^30.5.2, jest-expo@^58.0.14, prettier@^3.9.10, @types/jest@^30.0.0, @types/react@~19.3.0, typescript@~6.0.3 (+11 more)

### Community 10 - "Daily Spark 🔥"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Daily Spark 🔥, Data model, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 11 - "Composer Scripts"
Cohesion: 0.14
Nodes (14): scripts, analyse, check, dev, lint, lint:check, logs, post-autoload-dump (+6 more)

### Community 12 - "Mobile NPM Scripts"
Cohesion: 0.15
Nodes (13): scripts, android, check, format, format:check, ios, lint, lint:fix (+5 more)

### Community 13 - "ci.yml"
Cohesion: 0.14
Nodes (13): name, runs-on, concurrency, cancel-in-progress, group, jobs, api, mobile (+5 more)

### Community 14 - "ESLint Configuration"
Cohesion: 0.22
Nodes (8): { defineConfig }, expoConfig, noRawDesignValues, prettierConfig, eslint@^9.39.5, eslint-config-expo@^58.0.4, eslint, eslint-config-expo

### Community 15 - "PHP Dev Dependencies"
Cohesion: 0.20
Nodes (10): require-dev, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 16 - "Composer Package Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 17 - "PHP Runtime Dependencies"
Cohesion: 0.22
Nodes (9): require, laravel/ai, laravel/framework, laravel/sanctum, laravel/tinker, league/flysystem-aws-s3-v3, php, resend/resend-laravel (+1 more)

### Community 18 - "TypeScript Configuration"
Cohesion: 0.22
Nodes (8): compilerOptions, paths, strict, types, extends, include, @/assets/*, expo/tsconfig.base

### Community 20 - "Composer Script Descriptions"
Cohesion: 0.22
Nodes (9): scripts-descriptions, analyse, check, dev, lint, lint:check, logs, setup (+1 more)

### Community 21 - "Composer Plugin Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 23 - "PHP Autoload Mapping"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 26 - "Jest Configuration"
Cohesion: 0.40
Nodes (5): jest, collectCoverageFrom, preset, setupFilesAfterEnv, transformIgnorePatterns

### Community 29 - "Expo App Config"
Cohesion: 0.50
Nodes (3): config, expo@~58.0.23, expo

### Community 31 - "Love Map / Profiles 🗺️"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Love Map / Profiles 🗺️ (+4 more)

### Community 32 - "Laravel Package Discovery"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 36 - "Milestones & Countdowns ⏳"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Milestones & Countdowns ⏳ (+11 more)

### Community 37 - "Moments 📸"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Moments 📸 (+4 more)

### Community 38 - "On This Day 🕰️"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, On This Day 🕰️ (+4 more)

### Community 39 - "Shared Planner 🗓️"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+11 more)

### Community 40 - "On This Day 🕰️ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, On This Day 🕰️ - mobile, Tests, Theme usage

### Community 41 - "Wishlist & Gift Vault 🎁 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage, Wishlist & Gift Vault 🎁 - mobile

### Community 42 - "Weekly Recap (AI) ✨"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+4 more)

### Community 43 - "Wishlist & Gift Vault 🎁"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+4 more)

### Community 44 - "Year in Moments 📖"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+4 more)

### Community 45 - "Brand — Swoon 💞"
Cohesion: 0.08
Nodes (23): 1. Personality, 2. Voice, 3.1.1 The three planes, 3.1.2 Gradients, 3.1 How the tokens are layered, 3.2 Light theme, 3.3 Dark theme, 3.4 Accessibility — verified, not assumed (+15 more)

### Community 46 - "UX rules — Swoon"
Cohesion: 0.11
Nodes (17): 10. Forgiving actions, 11. Offline-tolerant, 12. Accessible by default, 13. Copy rules, 14. Navigation, 15. Consistency over novelty, 1. One primary action per screen, 2. Three taps to value (+9 more)

### Community 78 - "Architecture — Swoon"
Cohesion: 0.12
Nodes (16): 10. Environments, 1. Shape of the system, 2. Request lifecycle, 3.1 Privacy classes, 3.2 Conventions, 3. Data model, 4.1 Rules that make this safe, 4.2 What never reaches a prompt (+8 more)

### Community 79 - "Decisions — Swoon"
Cohesion: 0.09
Nodes (22): ADR-001 — "Swoon" is a placeholder in exactly two config values, ADR-002 — API-only Laravel: Vite and Blade removed, ADR-003 — Lint rule uses `no-restricted-syntax`, not a custom plugin, ADR-004 — Palette adjusted to meet WCAG AA, ADR-005 — Reveal gates are enforced server-side, ADR-006 — Model choice: Opus 5.5 strong, Haiku 5.5 light, ADR-007 — `laravel/ai` over the Anthropic PHP SDK, ADR-008 — Tests on SQLite by default, MySQL in CI (+14 more)

### Community 80 - "Dev tooling — Swoon"
Cohesion: 0.11
Nodes (18): 10. Hooks and pre-commit, 11. CI, 1. Prerequisites, 2. First run, 3. Running it, 4. Getting the app to reach the API ⚠️, 5. Checks, 6. Secrets you must add by hand (+10 more)

### Community 81 - "AI Platform ✨"
Cohesion: 0.14
Nodes (13): AI Platform ✨, AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO (+5 more)

### Community 82 - "AI prompts — Swoon"
Cohesion: 0.14
Nodes (13): AI prompts — Swoon, Changing a prompt, `date_pack`, `date_plan`, How prompts work, Index, `insights`, `moment_tagging` (+5 more)

### Community 83 - "<Module name> <emoji>"
Cohesion: 0.14
Nodes (13): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Mobile (+5 more)

### Community 84 - "Reminders ⏰"
Cohesion: 0.09
Nodes (21): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Mobile (+13 more)

### Community 85 - "Billing & Entitlements 🧾"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Billing & Entitlements 🧾, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 86 - "Bucket List 🪣"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Bucket List 🪣, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 87 - "Romantic Challenges 💌"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+4 more)

### Community 88 - "Check-in (Temperature Check) 🌡️"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Check-in (Temperature Check) 🌡️, Data model, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 89 - "Date Debrief 💬"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Date Debrief 💬, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 90 - "AI Date Planner 🍷"
Cohesion: 0.10
Nodes (19): AI Date Planner 🍷, AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 91 - "Date Roulette 🎡"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Date Roulette 🎡, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 92 - "Insights (AI) 💡"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Insights (AI) 💡, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 93 - "Love Notes 💕"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Love Notes 💕 (+4 more)

### Community 94 - "Phase 10 — Launch readiness"
Cohesion: 0.17
Nodes (12): `[ ]` P10.T01 — Accessibility audit on real devices · **M**, `[ ]` P10.T02 — Authorisation test completeness gate · **M**, `[ ]` P10.T03 — Security review: OWASP API Top 10 · **M**, `[ ]` P10.T04 — Performance pass · **M**, `[ ]` P10.T05 — Privacy policy, terms and the data map · **M**, `[ ]` P10.T06 — Analytics events · **S**, `[ ]` P10.T07 — Final product name decision · **S**, `[ ]` P10.T08 — Store assets and listings · **M** (+4 more)

### Community 95 - "Accounts & Couple Linking 💞"
Cohesion: 0.18
Nodes (11): Accounts & Couple Linking 💞, AI usage (if any), API endpoints, Business rules, Jobs, events & schedules, Known gaps / TODO, Mobile, Permissions & privacy (+3 more)

### Community 96 - "Phase 9 — More modules"
Cohesion: 0.18
Nodes (11): `[ ]` P9.T01 — Love Map fields and entries · **M**, `[ ]` P9.T02 — Love Map UI · **S**, `[ ]` P9.T03 — Shared wishlist · **S**, `[ ]` P9.T04 — Gift vault · **M**, `[ ]` P9.T05 — Bucket list · **S**, `[ ]` P9.T06 — Date debrief · **M**, `[ ]` P9.T07 — Check-in: responses and privacy · **M**, `[ ]` P9.T08 — Check-in combined trend · **M** (+3 more)

### Community 97 - "Pricing — Swoon"
Cohesion: 0.18
Nodes (10): 1. The model, 2. Tiers, 3. Why these numbers, 4. High-ticket add-ons, 5. Payment rails — the rule that cannot be broken, 6. Entitlements — server-side, always, 7. AI cost control, 8. Metrics to watch (+2 more)

### Community 98 - "test-mysql"
Cohesion: 0.18
Nodes (11): MYSQL_DATABASE, MYSQL_ROOT_PASSWORD, test-mysql, env, image, options, ports, mysql (+3 more)

### Community 99 - "Accounts & Couple Linking 💞 - mobile"
Cohesion: 0.25
Nodes (8): Accounts & Couple Linking 💞 - mobile, Changelog, Known gaps / TODO, Local state, Offline behaviour, Screens, navigation, components, Tests, Theme usage

### Community 100 - "CLAUDE.md — Swoon"
Cohesion: 0.20
Nodes (10): AI rules, Brand and UX, CLAUDE.md — Swoon, Documentation rule, graphify, Minimalism, OmniRoute, Privacy and safety (non-negotiable) (+2 more)

### Community 101 - "Phase 2 — AI platform"
Cohesion: 0.20
Nodes (10): `[ ]` P2.T01 — AI driver abstraction and the fake · **M**, `[ ]` P2.T02 — Prompt registry with versioning · **S**, `[ ]` P2.T03 — Structured output validation · **S**, `[ ]` P2.T04 — SafetyGuard · **M**, `[ ]` P2.T05 — Privacy filter (allow-list) · **M**, `[ ]` P2.T06 — Cost tracking and `ai_usage` · **S**, `[ ]` P2.T07 — Caps and the cost guard · **M**, `[ ]` P2.T08 — Queued `RunAiTask` and the full pipeline · **M** (+2 more)

### Community 102 - "Phase 3 — AI Date Planner"
Cohesion: 0.20
Nodes (10): `[ ]` P3.T01 — Date plan schema and models · **S**, `[ ]` P3.T02 — The reveal gate · **M**, `[ ]` P3.T03 — The `date_plan` prompt and generation job · **M**, `[ ]` P3.T04 — Questionnaire UI · **M**, `[ ]` P3.T05 — Waiting, nudge and reveal · **M**, `[ ]` P3.T06 — Options, hearts and lock-in · **M**, `[ ]` P3.T07 — Reminders module core · **M**, `[ ]` P3.T08 — Local notifications on device · **M** (+2 more)

### Community 103 - "Phase 4 — Moments & On This Day"
Cohesion: 0.20
Nodes (10): `[ ]` P4.T01 — Moments schema and CRUD · **M**, `[ ]` P4.T02 — Private moments · **S**, `[ ]` P4.T03 — Media upload and signed URLs · **M**, `[ ]` P4.T04 — Photo and voice processing · **M**, `[ ]` P4.T05 — Undoable delete · **S**, `[ ]` P4.T06 — Offline create and sync · **M**, `[ ]` P4.T07 — Feed, search and tags · **M**, `[ ]` P4.T08 — On This Day logic · **M** (+2 more)

### Community 104 - "Phase 6 — Engagement"
Cohesion: 0.20
Nodes (10): `[ ]` P6.T01 — Daily Spark question library · **S**, `[ ]` P6.T02 — Daily Spark assignment and reveal · **M**, `[ ]` P6.T03 — Daily Spark UI · **S**, `[ ]` P6.T04 — Challenge library · **S**, `[ ]` P6.T05 — Weekly challenges and gentle streaks · **M**, `[ ]` P6.T06 — Love Notes · **M**, `[ ]` P6.T07 — Love Notes UI and local notifications · **S**, `[ ]` P6.T08 — Date idea library and Date Roulette · **M** (+2 more)

### Community 105 - "Billing & Entitlements 🧾 - mobile"
Cohesion: 0.29
Nodes (7): Billing & Entitlements 🧾 - mobile, Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 106 - "Love Notes 💕 - mobile"
Cohesion: 0.22
Nodes (7): Changelog, Known gaps / TODO, Local state, Love Notes 💕 - mobile, Offline behaviour, Tests, Theme usage

### Community 107 - "Phase 1 — Accounts & Couples"
Cohesion: 0.22
Nodes (9): `[ ]` P1.T02 — Email registration, login, logout · **M**, `[ ]` P1.T03 — Sign in with Apple and Google · **M**, `[ ]` P1.T04 — Partner invite and accept · **M**, `[ ]` P1.T05 — Deep links and universal links · **M**, `[ ]` P1.T06 — Onboarding flow · **M**, `[ ]` P1.T08 — Unlink · **S**, `[ ]` P1.T09 — Account deletion and data export · **M**, `[ ]` P1.T10 — Authorisation test sweep for Phase 1 · **S** (+1 more)

### Community 108 - "Phase 8 — Monetisation"
Cohesion: 0.22
Nodes (9): `[ ]` P8.T01 — Entitlements schema and the gate · **M**, `[ ]` P8.T02 — RevenueCat webhooks · **M**, `[ ]` P8.T03 — Trial logic · **S**, `[ ]` P8.T04 — Purchase flow in the app · **M**, `[ ]` P8.T05 — Paywall screens · **M**, `[ ]` P8.T06 — Caps surfaced kindly · **S**, `[ ]` P8.T07 — Unlink and downgrade behaviour · **M**, `[ ]` P8.T08 — Store-rules compliance audit · **S** (+1 more)

### Community 110 - "api/README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 111 - "Phase 11 — High-ticket add-ons"
Cohesion: 0.25
Nodes (8): `[ ]` P11.T01 — Year in Moments generation · **M**, `[ ]` P11.T02 — Year in Moments story UI and curation · **M**, `[ ]` P11.T03 — Choose a print partner · **S**, `[ ]` P11.T04 — Book layout, proof and order · **L → split** · **L**, `[ ]` P11.T05 — Premium date packs · **M**, `[ ]` P11.T06 — Concierge Occasion waitlist · **M**, `[ ]` P11.T07 — Gift Concierge · **M**, Phase 11 — High-ticket add-ons

### Community 112 - "Phase 7 — AI Recap & Insights"
Cohesion: 0.25
Nodes (8): `[ ]` P7.T01 — Recap schema and generation · **M**, `[ ]` P7.T02 — Recap card and history · **S**, `[ ]` P7.T03 — Recap email · **S**, `[ ]` P7.T04 — Recap safety path · **S**, `[ ]` P7.T05 — Insights generation · **M**, `[ ]` P7.T06 — Insights tone battery and safety · **M**, `[ ]` P7.T07 — Insights UI and feedback loop · **S**, Phase 7 — AI Recap & Insights

### Community 113 - "steps"
Cohesion: 0.54
Nodes (8): steps, steps, steps, name, run, uses, working-directory, steps

### Community 114 - "env"
Cohesion: 0.25
Nodes (8): AI_DRIVER, DB_CONNECTION, DB_DATABASE, DB_HOST, DB_PASSWORD, DB_PORT, DB_USERNAME, env

### Community 115 - "Insights (AI) 💡 - mobile"
Cohesion: 0.22
Nodes (7): Changelog, Insights (AI) 💡 - mobile, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 116 - "Swoon 💞"
Cohesion: 0.15
Nodes (8): Checks, Documentation, ⚠️ Getting the app to reach the API, Layout, Quick start, Swoon 💞, Tech, Things this app will never do

### Community 118 - "Build plan — Swoon 💞"
Cohesion: 0.29
Nodes (7): Appendix — things deliberately not built, Build plan — Swoon 💞, How to use this plan, Phase status, Rules for this plan, Session protocol, Status key

### Community 119 - "Phase 5 — Planner & Milestones"
Cohesion: 0.29
Nodes (7): `[ ]` P5.T01 — Planner events · **M**, `[ ]` P5.T02 — Planner UI · **M**, `[ ]` P5.T03 — Milestones · **M**, `[ ]` P5.T04 — Yearly event generation · **S**, `[ ]` P5.T05 — Days-together counter · **S**, `[ ]` P5.T06 — Authorisation sweep for Phases 3–5 · **S**, Phase 5 — Planner & Milestones

### Community 120 - "Welcome to your Expo app 👋"
Cohesion: 0.29
Nodes (6): Get a fresh project, Get started, Join the community, Learn more, Other setup steps, Welcome to your Expo app 👋

### Community 121 - "Moments 📸 - mobile"
Cohesion: 0.22
Nodes (7): Changelog, Known gaps / TODO, Local state, Moments 📸 - mobile, Offline behaviour, Tests, Theme usage

### Community 122 - "Romantic Challenges 💌 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Romantic Challenges 💌 - mobile, Tests, Theme usage

### Community 123 - "Weekly Recap (AI) ✨ - mobile"
Cohesion: 0.22
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage, Weekly Recap (AI) ✨ - mobile

### Community 124 - "Date Debrief 💬 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Date Debrief 💬 - mobile, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 125 - "Year in Moments 📖 - mobile"
Cohesion: 0.22
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage, Year in Moments 📖 - mobile

### Community 126 - "Bucket List 🪣 - mobile"
Cohesion: 0.29
Nodes (7): Bucket List 🪣 - mobile, Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 127 - "TestPingMail"
Cohesion: 0.05
Nodes (22): MailTest, QueueTest, PingJob, SwoonMailable, TestPingMail, {closure#1}(), {closure#3}(), {closure#4}() (+14 more)

### Community 129 - "Modules — Swoon"
Cohesion: 0.33
Nodes (6): By phase, Index, Keeping this file honest, Modules holding permanently private data, Modules — Swoon, Modules that use AI

### Community 131 - "Love Map / Profiles 🗺️ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Love Map / Profiles 🗺️ - mobile, Offline behaviour, Tests, Theme usage

### Community 133 - "Session log — Swoon"
Cohesion: 0.40
Nodes (4): 2026-11-05 — P0.T01 … P0.T09 · Project setup, 2026-11-05 — P0.T10 · MySQL connected, `users` schema live, 2026-11-05 — P0.T11 · Resend transport and the first real email, Session log — Swoon

### Community 134 - "docs"
Cohesion: 0.50
Nodes (4): if, name, runs-on, docs

### Community 135 - "on"
Cohesion: 0.50
Nodes (4): on, pull_request, push, branches

## Knowledge Gaps
- **4 isolated node(s):** `ADR-0004`, `ADR-0004`, `ADR-0019`, `expo/tsconfig.base`
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 839 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **54 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Card()` connect `Card` to `App Root & Theme Provider`, `Phase 4 — Moments & On This Day`?**
  _High betweenness centrality (0.101) - this node is a cross-community bridge._
- **Are the 40 inferred relationships involving `Card()` (e.g. with `Mobile` and `Mobile`) actually correct?**
  _`Card()` has 40 INFERRED edges - model-reasoned connections that need verification._
- **What connects `ADR-0004`, `ADR-0004`, `ADR-0019` to the rest of the system?**
  _4 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App Root & Theme Provider` be split into smaller, more focused modules?**
  _Cohesion score 0.05029838022165388 - nodes in this community are weakly interconnected._
- **Why does `Build plan — Swoon 💞` connect `Build plan — Swoon 💞` to `Phase 9 — More modules`, `Phase 2 — AI platform`, `Phase 3 — AI Date Planner`, `Phase 4 — Moments & On This Day`, `Phase 6 — Engagement`, `Phase 1 — Accounts & Couples`, `Phase 8 — Monetisation`, `Phase 11 — High-ticket add-ons`, `Phase 7 — AI Recap & Insights`, `Swoon 💞`, `Phase 5 — Planner & Milestones`, `Phase 10 — Launch readiness`, `TestPingMail`?**
  _High betweenness centrality (0.077) - this node is a cross-community bridge._
- **Are the 36 inferred relationships involving `Button()` (e.g. with `Mobile` and `Mobile`) actually correct?**
  _`Button()` has 36 INFERRED edges - model-reasoned connections that need verification._
- **Should `Card` be split into smaller, more focused modules?**
  _Cohesion score 0.07689003436426117 - nodes in this community are weakly interconnected._