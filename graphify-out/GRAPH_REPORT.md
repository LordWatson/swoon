# Graph Report - Smitten  (2026-11-05)

## Corpus Check
- 249 files · ~125,600 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 20, .example 1, .neon 1)

## Summary
- 2031 nodes · 3496 edges · 174 communities (115 shown, 59 thin omitted)
- Extraction: 88% EXTRACTED · 12% INFERRED · 0% AMBIGUOUS · INFERRED: 403 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `ba2dd468`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- with
- App Root & Theme Provider
- Button
- User
- Database Migrations
- package.json
- API HTTP Client
- Service Providers & Routing
- app/_layout.tsx
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
- Illuminate\Console\Command
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
- components/index.ts
- Weekly Recap (AI) ✨
- Wishlist & Gift Vault 🎁
- Year in Moments 📖
- Brand — Swoon 💞
- UX rules — Swoon
- Accounts/routes.php
- Architecture — Swoon
- Decisions — Swoon
- Dev tooling — Swoon
- AI Platform ✨
- AI prompts — Swoon
- <Module name> <emoji>
- Reminders ⏰
- Changelog
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
- Phase 8 — Monetisation
- CoupleFactory
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
- CoupleInvite
- Romantic Challenges 💌 - mobile
- Illuminate\Http\Request
- UserResource
- Year in Moments 📖 - mobile
- AccountsAuthorisationTest.php
- TestPingMail
- Couple
- Modules — Swoon
- SocialAuthTest.php
- Love Map / Profiles 🗺️ - mobile
- SwoonMailable
- Session log — Swoon
- docs
- on
- package.json
- CoupleTest.php
- expo-symbols@~58.0.4
- AcceptInvite
- onboarding.tsx
- api.ts
- PurgeDeletedUserData
- OidcTokenVerifier
- [code].tsx
- useAuth.tsx
- Illuminate\Contracts\Queue\ShouldQueue
- useTheme
- PingJob
- Phase 0 — Foundations
- PlaceholderScreen
- index.tsx
- PrivacyController.php
- InviteController
- API endpoints
- Component library
- Reminders ⏰ - mobile
- Check-in (Temperature Check) 🌡️ - mobile
- AI Date Planner 🍷 - mobile
- Milestones & Countdowns ⏳ - mobile
- 3. Running it
- expo-constants
- expo-haptics
- expo-web-browser@~58.0.7
- phosphor-react-native@^3.0.6
- react-native-screens@~4.30.1
- react-native-worklets@0.13.0

## God Nodes (most connected - your core abstractions)
1. `User` - 90 edges
2. `Button()` - 59 edges
3. `Card()` - 55 edges
4. `Couple` - 50 edges
5. `useTheme()` - 45 edges
6. `CoupleInvite` - 42 edges
7. `EmptyState()` - 37 edges
8. `Sheet()` - 34 edges
9. `Chip()` - 30 edges
10. `Changelog` - 30 edges

## Surprising Connections (you probably didn't know these)
- ``[x]` P0.T10 — Connect MySQL and run the first migration · **S**` --references--> `User`  [INFERRED]
  docs/PLAN.md → api/app/Models/User.php
- `2026-11-05 — P0.T10 · MySQL connected, `users` schema live` --references--> `User`  [INFERRED]
  docs/SESSION_LOG.md → api/app/Models/User.php
- ``[ ]` P5.T03 — Milestones · **M**` --references--> `CoupleLinked`  [INFERRED]
  docs/PLAN.md → api/app/Modules/Accounts/Events/CoupleLinked.php
- ``[ ]` P8.T03 — Trial logic · **S**` --references--> `CoupleLinked`  [INFERRED]
  docs/PLAN.md → api/app/Modules/Accounts/Events/CoupleLinked.php
- ``[ ]` P8.T07 — Unlink and downgrade behaviour · **M**` --references--> `CoupleUnlinked`  [INFERRED]
  docs/PLAN.md → api/app/Modules/Accounts/Events/CoupleUnlinked.php

## Import Cycles
- None detected.

## Communities (174 total, 59 thin omitted)

### Community 0 - "with"
Cohesion: 0.17
Nodes (12): with, cache, cache-dependency-path, coverage, extensions, fetch-depth, key, node-version (+4 more)

### Community 1 - "App Root & Theme Provider"
Cohesion: 0.06
Nodes (51): ADR-0019, ColourTokens, darkColours, lightColours, PartnerColourKey, partnerColourKeys, ADR-0004, haptic() (+43 more)

### Community 2 - "Button"
Cohesion: 0.16
Nodes (50): Mobile, Mobile, Mobile, Mobile, Mobile, Mobile, Mobile, Mobile (+42 more)

### Community 3 - "User"
Cohesion: 0.13
Nodes (3): User, Data model, UserPolicy

### Community 4 - "Database Migrations"
Cohesion: 0.11
Nodes (12): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+4 more)

### Community 5 - "package.json"
Cohesion: 0.08
Nodes (25): dependencies, expo-device@~58.0.5, expo-font@~58.0.11, @expo-google-fonts/fraunces@^0.4.1, @expo-google-fonts/nunito@^0.4.2, expo-linking@~58.0.16, expo-router@~58.0.30, expo-secure-store@~58.0.2 (+17 more)

### Community 6 - "API HTTP Client"
Cohesion: 0.16
Nodes (10): `[x]` P0.T06 — Health endpoint and typed API client · **S**, ApiError, HealthResponse, isRecord(), readToken(), RequestOptions, send(), SendOptions (+2 more)

### Community 7 - "Service Providers & Routing"
Cohesion: 0.21
Nodes (3): AppServiceProvider, ModuleServiceProvider, ADR-009 — Light modular monolith, no modules package

### Community 8 - "app/_layout.tsx"
Cohesion: 0.16
Nodes (19): queryClient, RootLayout(), renderThemed(), Me, AuthGate(), isWithin(), AuthState, authStateFor() (+11 more)

### Community 9 - "Mobile Dev Dependencies"
Cohesion: 0.13
Nodes (15): devDependencies, jest@^30.5.2, jest-expo@^58.0.14, prettier@^3.9.10, @testing-library/react-native@^14.1.0, @types/jest@^30.0.0, @types/react@~19.3.0, typescript@~6.0.3 (+7 more)

### Community 10 - "Daily Spark 🔥"
Cohesion: 0.10
Nodes (18): AI usage (if any), API endpoints, Business rules, Changelog, Daily Spark 🔥, Data model, Known gaps / TODO, Permissions & privacy (+10 more)

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
Cohesion: 0.18
Nodes (10): { defineConfig }, expoConfig, noRawDesignValues, prettierConfig, eslint@^9.39.5, eslint-config-expo@^58.0.4, eslint-config-prettier@^10.1.8, eslint (+2 more)

### Community 15 - "PHP Dev Dependencies"
Cohesion: 0.20
Nodes (10): require-dev, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 16 - "Composer Package Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 17 - "PHP Runtime Dependencies"
Cohesion: 0.20
Nodes (10): require, firebase/php-jwt, laravel/ai, laravel/framework, laravel/sanctum, laravel/tinker, league/flysystem-aws-s3-v3, php (+2 more)

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
Cohesion: 0.09
Nodes (22): AI usage (if any), API endpoints, Billing & Entitlements 🧾, Changelog, Data model, Known gaps / TODO, Permissions & privacy, Purpose (+14 more)

### Community 37 - "Moments 📸"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Moments 📸 (+11 more)

### Community 38 - "On This Day 🕰️"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, On This Day 🕰️ (+4 more)

### Community 39 - "Shared Planner 🗓️"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+11 more)

### Community 40 - "On This Day 🕰️ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, On This Day 🕰️ - mobile, Tests, Theme usage

### Community 41 - "components/index.ts"
Cohesion: 0.11
Nodes (25): AITagProps, AvatarPairProps, Partner, AnimatedPressable, ButtonProps, ButtonSize, ButtonVariant, styles (+17 more)

### Community 42 - "Weekly Recap (AI) ✨"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+11 more)

### Community 43 - "Wishlist & Gift Vault 🎁"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+11 more)

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
Cohesion: 0.14
Nodes (13): 10. Environments, 1. Shape of the system, 2. Request lifecycle, 4.1 Rules that make this safe, 4.2 What never reaches a prompt, 4.3 Tasks, 4. The AI pipeline, 5. Background work (+5 more)

### Community 79 - "Decisions — Swoon"
Cohesion: 0.09
Nodes (23): ADR-001 — "Swoon" is a placeholder in exactly two config values, ADR-002 — API-only Laravel: Vite and Blade removed, ADR-003 — Lint rule uses `no-restricted-syntax`, not a custom plugin, ADR-004 — Palette adjusted to meet WCAG AA, ADR-005 — Reveal gates are enforced server-side, ADR-006 — Model choice: Opus 5.5 strong, Haiku 5.5 light, ADR-007 — `laravel/ai` over the Anthropic PHP SDK, ADR-008 — Tests on SQLite by default, MySQL in CI (+15 more)

### Community 80 - "Dev tooling — Swoon"
Cohesion: 0.13
Nodes (15): 10. Hooks and pre-commit, 11. CI, 1. Prerequisites, 2. First run, 4.1 Invite links (P1.T05), 4. Getting the app to reach the API ⚠️, 5. Checks, 6. Secrets you must add by hand (+7 more)

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
Cohesion: 0.15
Nodes (13): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Mobile (+5 more)

### Community 85 - "Changelog"
Cohesion: 0.11
Nodes (17): DeleteAccount, UnlinkCouple, CoupleLinked, CoupleUnlinked, NotifyPartnerOfUnlink, Changelog, Business rules, Jobs, events & schedules (+9 more)

### Community 86 - "Bucket List 🪣"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Bucket List 🪣, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 87 - "Romantic Challenges 💌"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+4 more)

### Community 88 - "Check-in (Temperature Check) 🌡️"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Check-in (Temperature Check) 🌡️, Data model, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 89 - "Date Debrief 💬"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Date Debrief 💬, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 90 - "AI Date Planner 🍷"
Cohesion: 0.17
Nodes (12): AI Date Planner 🍷, AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 91 - "Date Roulette 🎡"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Date Roulette 🎡, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 92 - "Insights (AI) 💡"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Insights (AI) 💡, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 93 - "Love Notes 💕"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Love Notes 💕 (+11 more)

### Community 94 - "Phase 10 — Launch readiness"
Cohesion: 0.17
Nodes (12): `[ ]` P10.T01 — Accessibility audit on real devices · **M**, `[ ]` P10.T02 — Authorisation test completeness gate · **M**, `[ ]` P10.T03 — Security review: OWASP API Top 10 · **M**, `[ ]` P10.T04 — Performance pass · **M**, `[ ]` P10.T05 — Privacy policy, terms and the data map · **M**, `[ ]` P10.T06 — Analytics events · **S**, `[ ]` P10.T07 — Final product name decision · **S**, `[ ]` P10.T08 — Store assets and listings · **M** (+4 more)

### Community 95 - "Accounts & Couple Linking 💞"
Cohesion: 0.25
Nodes (8): Accounts & Couple Linking 💞, AI usage (if any), Business rules, Known gaps / TODO, Mobile, Purpose, Tests, User stories

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
Cohesion: 0.14
Nodes (14): Accounts & Couple Linking 💞 - mobile, Deep links and universal links (P1.T05), Known gaps / TODO, Local state, Offline behaviour, Onboarding (P1.T06), Onboarding (P1.T06), Screens, navigation, components (+6 more)

### Community 100 - "CLAUDE.md — Swoon"
Cohesion: 0.18
Nodes (11): AI rules, Brand and UX, CLAUDE.md — Swoon, Coding conventions, Documentation rule, graphify, Minimalism, OmniRoute (+3 more)

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

### Community 108 - "Phase 8 — Monetisation"
Cohesion: 0.22
Nodes (9): `[ ]` P8.T01 — Entitlements schema and the gate · **M**, `[ ]` P8.T02 — RevenueCat webhooks · **M**, `[ ]` P8.T03 — Trial logic · **S**, `[ ]` P8.T04 — Purchase flow in the app · **M**, `[ ]` P8.T05 — Paywall screens · **M**, `[ ]` P8.T06 — Caps surfaced kindly · **S**, `[ ]` P8.T07 — Unlink and downgrade behaviour · **M**, `[ ]` P8.T08 — Store-rules compliance audit · **S** (+1 more)

### Community 109 - "CoupleFactory"
Cohesion: 0.13
Nodes (3): CoupleFactory, CoupleInviteFactory, UserFactory

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
Cohesion: 0.29
Nodes (7): Changelog, Insights (AI) 💡 - mobile, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 116 - "Swoon 💞"
Cohesion: 0.15
Nodes (8): Checks, Documentation, ⚠️ Getting the app to reach the API, Layout, Quick start, Swoon 💞, Tech, Things this app will never do

### Community 117 - "Illuminate\Http\JsonResponse"
Cohesion: 0.15
Nodes (8): FindOrCreateSocialUser, {closure#1}(), {closure#2}(), SocialAuthController, SocialSignInRequest, SocialIdentity, GET /health(), GET /()

### Community 118 - "Build plan — Swoon 💞"
Cohesion: 0.29
Nodes (7): Appendix — things deliberately not built, Build plan — Swoon 💞, How to use this plan, Phase status, Rules for this plan, Session protocol, Status key

### Community 119 - "Phase 5 — Planner & Milestones"
Cohesion: 0.29
Nodes (7): `[ ]` P5.T01 — Planner events · **M**, `[ ]` P5.T02 — Planner UI · **M**, `[ ]` P5.T03 — Milestones · **M**, `[ ]` P5.T04 — Yearly event generation · **S**, `[ ]` P5.T05 — Days-together counter · **S**, `[ ]` P5.T06 — Authorisation sweep for Phases 3–5 · **S**, Phase 5 — Planner & Milestones

### Community 120 - "Welcome to your Expo app 👋"
Cohesion: 0.29
Nodes (6): Get a fresh project, Get started, Join the community, Learn more, Other setup steps, Welcome to your Expo app 👋

### Community 121 - "CoupleInvite"
Cohesion: 0.10
Nodes (9): SendInviteEmail, {closure#1}(), CoupleInvite, Jobs, events & schedules, TwoCouples, {closure#34}(), {closure#7}(), {closure#8}() (+1 more)

### Community 122 - "Romantic Challenges 💌 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Romantic Challenges 💌 - mobile, Tests, Theme usage

### Community 123 - "Illuminate\Http\Request"
Cohesion: 0.13
Nodes (9): CoupleController, CoupleResource, InvitePreviewResource, InviteResource, MeResource, PartnerResource, The invite flow (P1.T04), {closure#1}() (+1 more)

### Community 124 - "UserResource"
Cohesion: 0.11
Nodes (7): AuthenticateUser, RegisterUser, AuthController, LoginRequest, RegisterRequest, UserResource, `[x]` P1.T02 — Email registration, login, logout · **M**

### Community 125 - "Year in Moments 📖 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage, Year in Moments 📖 - mobile

### Community 126 - "AccountsAuthorisationTest.php"
Cohesion: 0.11
Nodes (14): accountsCases(), accountsRoutes(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#17}(), {closure#18}(), {closure#20}() (+6 more)

### Community 127 - "TestPingMail"
Cohesion: 0.17
Nodes (6): MailTest, TestPingMail, {closure#1}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 128 - "Couple"
Cohesion: 0.16
Nodes (9): Couple, Permissions & privacy, Unlink (P1.T08), CouplePolicy, {closure#1}(), {closure#2}(), {closure#3}(), DatabaseSeeder (+1 more)

### Community 129 - "Modules — Swoon"
Cohesion: 0.33
Nodes (6): By phase, Index, Keeping this file honest, Modules holding permanently private data, Modules — Swoon, Modules that use AI

### Community 130 - "SocialAuthTest.php"
Cohesion: 0.11
Nodes (24): appleClaims(), {closure#10}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+16 more)

### Community 131 - "Love Map / Profiles 🗺️ - mobile"
Cohesion: 0.22
Nodes (7): Changelog, Known gaps / TODO, Local state, Love Map / Profiles 🗺️ - mobile, Offline behaviour, Tests, Theme usage

### Community 132 - "SwoonMailable"
Cohesion: 0.12
Nodes (8): SwoonMailable, ExportReadyMail, InviteMail, UnlinkNoticeMail, {closure#33}(), {closure#23}(), {closure#14}(), 2026-11-05 — Phase 1 complete · Accounts & Couples (P1.T01 … P1.T10)

### Community 133 - "Session log — Swoon"
Cohesion: 0.33
Nodes (5): 2026-11-05 — P0.T01 … P0.T09 · Project setup, 2026-11-05 — P0.T10 · MySQL connected, `users` schema live, 2026-11-05 — P0.T11 · Resend transport and the first real email, 2026-11-05 — Review and audit fixes (no task ID), Session log — Swoon

### Community 134 - "docs"
Cohesion: 0.50
Nodes (4): if, name, runs-on, docs

### Community 135 - "on"
Cohesion: 0.50
Nodes (4): on, pull_request, push, branches

### Community 136 - "package.json"
Cohesion: 0.11
Nodes (18): expo-glass-effect@~58.0.5, expo-image@~58.0.18, expo-splash-screen@~58.0.13, expo-status-bar@~58.0.4, expo-system-ui@~58.0.7, @expo/ui@~58.0.27, react-native-gesture-handler@~3.3.0, main (+10 more)

### Community 137 - "CoupleTest.php"
Cohesion: 0.11
Nodes (7): {closure#10}(), {closure#12}(), {closure#14}(), {closure#15}(), {closure#19}(), {closure#5}(), {closure#6}()

### Community 140 - "AcceptInvite"
Cohesion: 0.11
Nodes (8): AcceptInvite, {closure#1}(), {closure#1}(), CreateInvite, RevokeInvite, {closure#1}(), {closure#3}(), `[x]` P1.T04 — Partner invite and accept · **M**

### Community 141 - "onboarding.tsx"
Cohesion: 0.22
Nodes (19): detectTimezone(), OnboardingScreen(), advance(), patchFor(), Changelog, ACCESSIBILITY_CHOICES, DIETARY_CHOICES, isSkippable() (+11 more)

### Community 142 - "api.ts"
Cohesion: 0.15
Nodes (20): apiRequest(), acceptInvite(), AuthResult, CoupleSummary, createInvite(), deleteAccount(), Entitlement, fetchMe() (+12 more)

### Community 144 - "PurgeDeletedUserData"
Cohesion: 0.13
Nodes (9): BuildDataExport, {closure#1}(), {closure#1}(), PurgeDeletedUserData, {closure#11}(), {closure#12}(), 3.1 Privacy classes, 3.2 Conventions (+1 more)

### Community 145 - "OidcTokenVerifier"
Cohesion: 0.20
Nodes (7): Social sign-in (P1.T03), InvalidIdentityToken, {closure#1}(), OidcTokenVerifier, SocialIdentityProvider, ADR-022 — firebase/php-jwt rather than hand-rolled token verification, `[x]` P1.T03 — Sign in with Apple and Google · **M**

### Community 146 - "[code].tsx"
Cohesion: 0.25
Nodes (12): AcceptInviteScreen(), InviteScreen(), InviteCodeShape, parseInviteLink(), clearPendingInvite(), readPendingInvite(), rememberPendingInvite(), useAcceptInvite() (+4 more)

### Community 148 - "useAuth.tsx"
Cohesion: 0.21
Nodes (11): setTokenReader(), SignInScreen(), detectTimezone(), SignUpScreen(), clearToken(), readToken(), saveToken(), installTokenReader() (+3 more)

### Community 150 - "Illuminate\Contracts\Queue\ShouldQueue"
Cohesion: 0.21
Nodes (4): BuildExportForUser, SendUnlinkNotice, {closure#22}(), {closure#15}()

### Community 151 - "useTheme"
Cohesion: 0.24
Nodes (11): 9. Implementation contract, TabsLayout(), AnimatedPressable, ChipProps, QuickAddOption, QuickAddSheet(), QuickAddSheetProps, usePressFeedback() (+3 more)

### Community 152 - "PingJob"
Cohesion: 0.22
Nodes (4): PingJob, {closure#2}(), `[x]` P0.T12 — Queue, scheduler and Maestro harness · **S**, 2026-11-05 — P0.T12 · Queue, scheduler, Maestro harness — **Phase 0 complete**

### Community 153 - "Phase 0 — Foundations"
Cohesion: 0.18
Nodes (11): Phase 0 — Foundations, `[x]` P0.T01 — Monorepo and Laravel 13 API · **M**, `[x]` P0.T02 — Expo app with Expo Router and TS strict · **M**, `[x]` P0.T03 — Theme tokens and the contrast gate · **M**, `[x]` P0.T05 — Linting and static analysis · **S**, `[x]` P0.T07 — Documentation system · **M**, `[x]` P0.T08 — Hooks, gitignore, pre-commit, CI · **S**, `[x]` P0.T09 — Graphify graph · **S** (+3 more)

### Community 154 - "PlaceholderScreen"
Cohesion: 0.33
Nodes (6): DatesScreen(), MomentsScreen(), PlannerScreen(), UsScreen(), PlaceholderScreen(), PlaceholderScreenProps

### Community 155 - "index.tsx"
Cohesion: 0.31
Nodes (7): fetchHealth(), WelcomeScreen(), Hero(), API_BASE_URL, APP_NAME, DEEP_LINK_SCHEME, The shell (P1.T07)

### Community 158 - "API endpoints"
Cohesion: 0.22
Nodes (9): API endpoints, Deletion and export (P1.T09), How sign-in refuses, Password policy, Rate limiting, The export JSON shape — settled (closes the gap below), The nudge and the profile update (P1.T06), The nudge (P1.T06) (+1 more)

### Community 159 - "Component library"
Cohesion: 0.25
Nodes (7): Adding one, Component library, Controls, Internal, Surfaces, Tests, The design language in one paragraph

### Community 160 - "Reminders ⏰ - mobile"
Cohesion: 0.25
Nodes (8): Changelog, Known gaps / TODO, Local state, Offline behaviour, Reminders ⏰ - mobile, Screens, navigation, components, Tests, Theme usage

### Community 161 - "Check-in (Temperature Check) 🌡️ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Check-in (Temperature Check) 🌡️ - mobile, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 162 - "AI Date Planner 🍷 - mobile"
Cohesion: 0.29
Nodes (7): AI Date Planner 🍷 - mobile, Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 163 - "Milestones & Countdowns ⏳ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Milestones & Countdowns ⏳ - mobile, Offline behaviour, Tests, Theme usage

### Community 164 - "3. Running it"
Cohesion: 0.50
Nodes (4): 3. Running it, API — Herd serves HTTP; it does **not** run the queue or scheduler, Mobile, Proving the queue and the scheduler actually run

## Knowledge Gaps
- **4 isolated node(s):** `ADR-0004`, `ADR-0004`, `ADR-0019`, `expo/tsconfig.base`
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1034 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **59 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Couple`, `SocialAuthTest.php`, `SwoonMailable`, `Session log — Swoon`, `CoupleTest.php`, `PrivacyTest.php`, `AcceptInvite`, `PurgeDeletedUserData`, `UnlinkTest.php`, `ProfileTest.php`, `Illuminate\Contracts\Queue\ShouldQueue`, `Phase 0 — Foundations`, `PrivacyController.php`, `Changelog`, `AuthTest.php`, `InviteTest.php`, `CoupleFactory`, `Illuminate\Http\JsonResponse`, `CoupleInvite`, `Illuminate\Http\Request`, `UserResource`?**
  _High betweenness centrality (0.154) - this node is a cross-community bridge._
- **Are the 9 inferred relationships involving `User` (e.g. with `Changelog` and `Data model`) actually correct?**
  _`User` has 9 INFERRED edges - model-reasoned connections that need verification._
- **What connects `ADR-0004`, `ADR-0004`, `ADR-0019` to the rest of the system?**
  _4 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App Root & Theme Provider` be split into smaller, more focused modules?**
  _Cohesion score 0.05706214689265537 - nodes in this community are weakly interconnected._
- **Why does `Phase 1 — Accounts & Couples` connect `Changelog` to `Couple`, `Button`, `AcceptInvite`, `OidcTokenVerifier`, `Build plan — Swoon 💞`, `UserResource`?**
  _High betweenness centrality (0.086) - this node is a cross-community bridge._
- **Are the 36 inferred relationships involving `Button()` (e.g. with `Mobile` and `Mobile`) actually correct?**
  _`Button()` has 36 INFERRED edges - model-reasoned connections that need verification._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.12615384615384614 - nodes in this community are weakly interconnected._