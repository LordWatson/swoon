# Graph Report - Smitten  (2026-11-05)

## Corpus Check
- 326 files · ~176,340 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 20, .example 1, .neon 1)

## Summary
- 2762 nodes · 5294 edges · 210 communities (146 shown, 64 thin omitted)
- Extraction: 90% EXTRACTED · 10% INFERRED · 0% AMBIGUOUS · INFERRED: 555 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `0e0a6979`
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
- PipelineTest.php
- MODULES.md
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
- BuildDataExport
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
- .make
- Illuminate\Http\Request
- UserResource
- SafetyGuardTest.php
- AccountsAuthorisationTest.php
- TestPingMail
- Couple
- CapGuard
- SocialAuthTest.php
- Love Map / Profiles 🗺️ - mobile
- SwoonMailable
- PrivacyFilterTest.php
- docs
- on
- package.json
- CoupleTest.php
- UsageLogger
- PrivacyTest.php
- InviteController.php
- onboarding.tsx
- api.ts
- AiGeneration
- OidcTokenVerifier
- [code].tsx
- UnlinkTest.php
- useAuth.tsx
- SendInviteEmail
- (tabs)/_layout.tsx
- Phase 0 — Foundations
- useTheme
- Hero
- index.tsx
- AiDriver
- UsageLoggerTest.php
- SchemaValidatorTest.php
- Changelog
- PromptRegistryTest.php
- AnthropicDriverTest.php
- SchemaValidator
- Milestones & Countdowns ⏳ - mobile
- 3. Running it
- TwoCouples
- FakeDriver
- expo-web-browser@~58.0.7
- FakeDriverTest.php
- SafetyVerdict
- react-native-worklets@0.13.0
- AiRequest
- Illuminate\Contracts\JsonSchema\JsonSchema
- PromptRegistry
- SeederTest.php
- EvalCommandTest.php
- GenerateValidatedTest.php
- TaskSchemas
- Billing & Entitlements 🧾
- inventory.test.tsx
- AiService
- What is real and what is not
- Bucket List 🪣 - mobile
- Daily Spark 🔥 - mobile
- Moments 📸 - mobile
- Shared Planner 🗓️ - mobile
- Wishlist & Gift Vault 🎁 - mobile
- CoupleLinked
- AppServiceProvider
- resources/prompts/safety_screen/v1.md
- safety_screen battery
- sign-up.tsx
- expo-device@~58.0.5
- @expo-google-fonts/nunito
- expo-image@~58.0.18
- expo-linking@~58.0.16
- expo-splash-screen@~58.0.13
- expo-status-bar
- @expo/ui@~58.0.27

## God Nodes (most connected - your core abstractions)
1. `User` - 104 edges
2. `Couple` - 68 edges
3. `Button()` - 61 edges
4. `Card()` - 59 edges
5. `AiRequest` - 51 edges
6. `useTheme()` - 47 edges
7. `CoupleInvite` - 42 edges
8. `UsageLogger` - 34 edges
9. `Sheet()` - 34 edges
10. `EmptyState()` - 33 edges

## Surprising Connections (you probably didn't know these)
- `2026-11-05 — P0.T10 · MySQL connected, `users` schema live` --references--> `User`  [INFERRED]
  docs/SESSION_LOG.md → api/app/Models/User.php
- ``[ ]` P5.T03 — Milestones · **M**` --references--> `CoupleLinked`  [INFERRED]
  docs/PLAN.md → api/app/Modules/Accounts/Events/CoupleLinked.php
- ``[ ]` P8.T03 — Trial logic · **S**` --references--> `CoupleLinked`  [INFERRED]
  docs/PLAN.md → api/app/Modules/Accounts/Events/CoupleLinked.php
- ``[ ]` P8.T07 — Unlink and downgrade behaviour · **M**` --references--> `CoupleUnlinked`  [INFERRED]
  docs/PLAN.md → api/app/Modules/Accounts/Events/CoupleUnlinked.php
- `AI rules` --references--> `AiService`  [INFERRED]
  CLAUDE.md → api/app/Modules/Ai/Actions/AiService.php

## Import Cycles
- None detected.

## Communities (210 total, 64 thin omitted)

### Community 0 - "with"
Cohesion: 0.17
Nodes (12): with, cache, cache-dependency-path, coverage, extensions, fetch-depth, key, node-version (+4 more)

### Community 1 - "App Root & Theme Provider"
Cohesion: 0.07
Nodes (41): flatten(), styleOf(), ColourTokens, darkColours, lightColours, PartnerColourKey, partnerColourKeys, PartnerColourName (+33 more)

### Community 2 - "Button"
Cohesion: 0.16
Nodes (50): Mobile, Mobile, Mobile, Mobile, Mobile, Mobile, Mobile, Mobile (+42 more)

### Community 4 - "Database Migrations"
Cohesion: 0.10
Nodes (15): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+7 more)

### Community 5 - "package.json"
Cohesion: 0.09
Nodes (23): dependencies, expo-constants@~58.0.15, expo-font@~58.0.11, expo-glass-effect@~58.0.5, @expo-google-fonts/fraunces@^0.4.1, expo-router@~58.0.30, expo-secure-store@~58.0.2, expo-symbols@~58.0.4 (+15 more)

### Community 6 - "API HTTP Client"
Cohesion: 0.16
Nodes (11): `[x]` P0.T06 — Health endpoint and typed API client · **S**, ApiError, fetchHealth(), HealthResponse, isRecord(), readToken(), RequestOptions, send() (+3 more)

### Community 7 - "Service Providers & Routing"
Cohesion: 0.18
Nodes (7): ModuleServiceProvider, ADR-009 — Light modular monolith, no modules package, 2026-11-05 — P0.T01 … P0.T09 · Project setup, 2026-11-05 — P0.T10 · MySQL connected, `users` schema live, 2026-11-05 — P0.T11 · Resend transport and the first real email, 2026-11-05 — Review and audit fixes (no task ID), Session log — Swoon

### Community 8 - "app/_layout.tsx"
Cohesion: 0.17
Nodes (18): @testing-library/react-native@^14.1.0, queryClient, RootLayout(), renderThemed(), Me, AuthGate(), ALLOWED_ROUTES, AuthState (+10 more)

### Community 9 - "Mobile Dev Dependencies"
Cohesion: 0.15
Nodes (13): devDependencies, jest@^30.5.2, jest-expo@^58.0.14, prettier@^3.9.10, @types/jest@^30.0.0, @types/react@~19.3.0, typescript@~6.0.3, jest (+5 more)

### Community 10 - "Daily Spark 🔥"
Cohesion: 0.18
Nodes (11): AI usage (if any), API endpoints, Business rules, Changelog, Daily Spark 🔥, Data model, Known gaps / TODO, Permissions & privacy (+3 more)

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

### Community 27 - "Illuminate\Console\Command"
Cohesion: 0.13
Nodes (4): MailTest, QueueTest, RemindInvites, ReportCosts

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
Cohesion: 0.18
Nodes (11): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Known gaps / TODO, Milestones & Countdowns ⏳, Permissions & privacy (+3 more)

### Community 37 - "Moments 📸"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Moments 📸 (+4 more)

### Community 38 - "On This Day 🕰️"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, On This Day 🕰️ (+4 more)

### Community 39 - "Shared Planner 🗓️"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+4 more)

### Community 40 - "On This Day 🕰️ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, On This Day 🕰️ - mobile, Tests, Theme usage

### Community 41 - "components/index.ts"
Cohesion: 0.12
Nodes (22): AITagProps, AvatarPairProps, Partner, AnimatedPressable, ButtonProps, ButtonSize, ButtonVariant, styles (+14 more)

### Community 42 - "Weekly Recap (AI) ✨"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+11 more)

### Community 43 - "Wishlist & Gift Vault 🎁"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+4 more)

### Community 44 - "Year in Moments 📖"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+11 more)

### Community 45 - "Brand — Swoon 💞"
Cohesion: 0.08
Nodes (23): 1. Personality, 2. Voice, 3.1.1 The three planes, 3.1.2 Gradients, 3.1 How the tokens are layered, 3.2 Light theme, 3.3 Dark theme, 3.4 Accessibility — verified, not assumed (+15 more)

### Community 46 - "UX rules — Swoon"
Cohesion: 0.11
Nodes (17): 10. Forgiving actions, 11. Offline-tolerant, 12. Accessible by default, 13. Copy rules, 14. Navigation, 15. Consistency over novelty, 1. One primary action per screen, 2. Three taps to value (+9 more)

### Community 47 - "Accounts/routes.php"
Cohesion: 0.28
Nodes (4): MeController, UpdateProfileRequest, MeResource, Caps and the cost guards

### Community 48 - "PipelineTest.php"
Cohesion: 0.07
Nodes (40): AiGenerationCompleted, AiGenerationFailed, AiSafetyTripped, Jobs, events & schedules, Regenerate, The chain, ai(), {closure#10}() (+32 more)

### Community 77 - "MODULES.md"
Cohesion: 0.10
Nodes (6): By phase, Index, Keeping this file honest, Modules holding permanently private data, Modules — Swoon, Modules that use AI

### Community 78 - "Architecture — Swoon"
Cohesion: 0.14
Nodes (13): 10. Environments, 1. Shape of the system, 2. Request lifecycle, 4.1 Rules that make this safe, 4.2 What never reaches a prompt, 4.3 Tasks, 4. The AI pipeline, 5. Background work (+5 more)

### Community 79 - "Decisions — Swoon"
Cohesion: 0.08
Nodes (24): ADR-001 — "Swoon" is a placeholder in exactly two config values, ADR-002 — API-only Laravel: Vite and Blade removed, ADR-003 — Lint rule uses `no-restricted-syntax`, not a custom plugin, ADR-004 — Palette adjusted to meet WCAG AA, ADR-005 — Reveal gates are enforced server-side, ADR-006 — Model choice: Opus 5.5 strong, Haiku 5.5 light, ADR-008 — Tests on SQLite by default, MySQL in CI, ADR-010 — No remote push notifications (+16 more)

### Community 80 - "Dev tooling — Swoon"
Cohesion: 0.12
Nodes (16): 10. Hooks and pre-commit, 11. CI, 1. Prerequisites, 2. First run, 4.1 Invite links (P1.T05), 4. Getting the app to reach the API ⚠️, 5. Checks, 6. Secrets you must add by hand (+8 more)

### Community 81 - "AI Platform ✨"
Cohesion: 0.12
Nodes (13): AI Platform ✨, AI usage (if any), API endpoints, Business rules, Data model, Evals, Mobile, Permissions & privacy (+5 more)

### Community 82 - "AI prompts — Swoon"
Cohesion: 0.12
Nodes (15): AI prompts — Swoon, Changing a prompt, `date_pack`, `date_plan`, How prompts work, Index, `insights`, `moment_tagging` (+7 more)

### Community 83 - "<Module name> <emoji>"
Cohesion: 0.14
Nodes (13): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Mobile (+5 more)

### Community 84 - "Reminders ⏰"
Cohesion: 0.09
Nodes (21): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Mobile (+13 more)

### Community 85 - "Changelog"
Cohesion: 0.13
Nodes (10): DeleteAccount, UnlinkCouple, PrivacyController, Changelog, Phase 1 — Accounts & Couples, `[x]` P1.T05 — Deep links and universal links · **M**, `[x]` P1.T06 — Onboarding flow · **M**, `[x]` P1.T08 — Unlink · **S** (+2 more)

### Community 86 - "Bucket List 🪣"
Cohesion: 0.17
Nodes (12): AI usage (if any), API endpoints, Bucket List 🪣, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO (+4 more)

### Community 87 - "Romantic Challenges 💌"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Permissions & privacy (+11 more)

### Community 88 - "Check-in (Temperature Check) 🌡️"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Check-in (Temperature Check) 🌡️, Data model, Jobs, events & schedules, Known gaps / TODO (+11 more)

### Community 89 - "Date Debrief 💬"
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Date Debrief 💬, Jobs, events & schedules, Known gaps / TODO (+11 more)

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
Cohesion: 0.10
Nodes (19): AI usage (if any), API endpoints, Business rules, Changelog, Data model, Jobs, events & schedules, Known gaps / TODO, Love Notes 💕 (+11 more)

### Community 94 - "Phase 10 — Launch readiness"
Cohesion: 0.17
Nodes (12): `[ ]` P10.T01 — Accessibility audit on real devices · **M**, `[ ]` P10.T02 — Authorisation test completeness gate · **M**, `[ ]` P10.T03 — Security review: OWASP API Top 10 · **M**, `[ ]` P10.T04 — Performance pass · **M**, `[ ]` P10.T05 — Privacy policy, terms and the data map · **M**, `[ ]` P10.T06 — Analytics events · **S**, `[ ]` P10.T07 — Final product name decision · **S**, `[ ]` P10.T08 — Store assets and listings · **M** (+4 more)

### Community 95 - "BuildDataExport"
Cohesion: 0.08
Nodes (21): BuildDataExport, Accounts & Couple Linking 💞, AI usage (if any), API endpoints, Business rules, Deletion and export (P1.T09), How sign-in refuses, Known gaps / TODO (+13 more)

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
Cohesion: 0.10
Nodes (22): Accounts & Couple Linking 💞 - mobile, Changelog, Deep links and universal links (P1.T05), Home showed invented data (fixed 2026-11-05), How to know what is real, Known gaps / TODO, Local state, Offline behaviour (+14 more)

### Community 100 - "CLAUDE.md — Swoon"
Cohesion: 0.18
Nodes (11): AI rules, Brand and UX, CLAUDE.md — Swoon, Coding conventions, Documentation rule, graphify, Minimalism, OmniRoute (+3 more)

### Community 101 - "Phase 2 — AI platform"
Cohesion: 0.22
Nodes (9): Phase 2 — AI platform, `[x]` P2.T01 — AI driver abstraction and the fake · **M**, `[x]` P2.T02 — Prompt registry with versioning · **S**, `[x]` P2.T03 — Structured output validation · **S**, `[x]` P2.T04 — SafetyGuard · **M**, `[x]` P2.T05 — Privacy filter (allow-list) · **M**, `[x]` P2.T06 — Cost tracking and `ai_usage` · **S**, `[x]` P2.T07 — Caps and the cost guard · **M** (+1 more)

### Community 102 - "Phase 3 — AI Date Planner"
Cohesion: 0.22
Nodes (9): `[ ]` P3.T02 — The reveal gate · **M**, `[ ]` P3.T03 — The `date_plan` prompt and generation job · **M**, `[ ]` P3.T04 — Questionnaire UI · **M**, `[ ]` P3.T05 — Waiting, nudge and reveal · **M**, `[ ]` P3.T06 — Options, hearts and lock-in · **M**, `[ ]` P3.T07 — Reminders module core · **M**, `[ ]` P3.T08 — Local notifications on device · **M**, `[ ]` P3.T09 — Schedule a date end to end · **S** (+1 more)

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
Cohesion: 0.15
Nodes (5): AcceptInvite, {closure#1}(), {closure#1}(), {closure#1}(), CoupleInvite

### Community 122 - ".make"
Cohesion: 0.11
Nodes (29): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), caps() (+21 more)

### Community 123 - "Illuminate\Http\Request"
Cohesion: 0.10
Nodes (12): CoupleController, CoupleResource, InvitePreviewResource, InviteResource, PartnerResource, The export JSON shape — settled (closes the gap below), The invite flow (P1.T04), AllowanceResource (+4 more)

### Community 124 - "UserResource"
Cohesion: 0.11
Nodes (8): AuthenticateUser, RegisterUser, AuthController, LoginRequest, RegisterRequest, UserResource, AiFeedbackRequest, `[x]` P1.T02 — Email registration, login, logout · **M**

### Community 125 - "SafetyGuardTest.php"
Cohesion: 0.10
Nodes (30): battery(), {closure#1}(), {closure#10}(), {closure#14}(), {closure#15}(), {closure#17}(), {closure#18}(), {closure#19}() (+22 more)

### Community 126 - "AccountsAuthorisationTest.php"
Cohesion: 0.09
Nodes (14): accountsCases(), accountsRoutes(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#17}(), {closure#18}(), {closure#20}() (+6 more)

### Community 127 - "TestPingMail"
Cohesion: 0.27
Nodes (5): TestPingMail, {closure#1}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 128 - "Couple"
Cohesion: 0.15
Nodes (12): {closure#1}(), Couple, Data model, Permissions & privacy, Unlink (P1.T08), CouplePolicy, {closure#1}(), {closure#2}() (+4 more)

### Community 129 - "CapGuard"
Cohesion: 0.10
Nodes (14): CapGuard, {closure#1}(), Tests, CapReason, Allowed, CoupleCostGuard, GlobalCostGuard, MonthlyCap (+6 more)

### Community 130 - "SocialAuthTest.php"
Cohesion: 0.11
Nodes (25): {closure#1}(), appleClaims(), {closure#10}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}() (+17 more)

### Community 131 - "Love Map / Profiles 🗺️ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Love Map / Profiles 🗺️ - mobile, Offline behaviour, Tests, Theme usage

### Community 132 - "SwoonMailable"
Cohesion: 0.11
Nodes (9): SwoonMailable, ExportReadyMail, InviteMail, UnlinkNoticeMail, {closure#33}(), {closure#22}(), {closure#23}(), {closure#14}() (+1 more)

### Community 133 - "PrivacyFilterTest.php"
Cohesion: 0.08
Nodes (22): TaskAllowList, {closure#10}(), {closure#11}(), {closure#12}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+14 more)

### Community 134 - "docs"
Cohesion: 0.50
Nodes (4): if, name, runs-on, docs

### Community 135 - "on"
Cohesion: 0.50
Nodes (4): on, pull_request, push, branches

### Community 136 - "package.json"
Cohesion: 0.12
Nodes (16): expo-haptics@~58.0.7, expo-system-ui@~58.0.7, phosphor-react-native@^3.0.6, react-native-screens@~4.30.1, react-native-svg@15.16.0, react-native-web@~0.21.4, main, name (+8 more)

### Community 137 - "CoupleTest.php"
Cohesion: 0.10
Nodes (8): {closure#1}(), {closure#10}(), {closure#12}(), {closure#14}(), {closure#15}(), {closure#19}(), {closure#5}(), {closure#6}()

### Community 138 - "UsageLogger"
Cohesion: 0.11
Nodes (16): RunAiTask, AiOutcome, Capped, Error, InvalidOutput, Ok, SafetyTripped, UsageLogger (+8 more)

### Community 139 - "PrivacyTest.php"
Cohesion: 0.07
Nodes (4): {closure#1}(), PurgeDeletedUserData, {closure#11}(), {closure#12}()

### Community 140 - "InviteController.php"
Cohesion: 0.10
Nodes (7): CreateInvite, RevokeInvite, InviteController, InviteCode, AllowanceController, {closure#3}(), `[x]` P1.T04 — Partner invite and accept · **M**

### Community 141 - "onboarding.tsx"
Cohesion: 0.24
Nodes (18): detectTimezone(), OnboardingScreen(), advance(), patchFor(), ACCESSIBILITY_CHOICES, DIETARY_CHOICES, isSkippable(), nextStep() (+10 more)

### Community 142 - "api.ts"
Cohesion: 0.16
Nodes (19): apiRequest(), acceptInvite(), AuthResult, createInvite(), deleteAccount(), Entitlement, fetchMe(), Invite (+11 more)

### Community 144 - "AiGeneration"
Cohesion: 0.10
Nodes (7): AiFeedback, AiGeneration, {closure#1}(), AiUsage, Known gaps / TODO, AiGenerationPolicy, {closure#26}()

### Community 145 - "OidcTokenVerifier"
Cohesion: 0.17
Nodes (5): InvalidIdentityToken, OidcTokenVerifier, SocialIdentityProvider, ADR-022 — firebase/php-jwt rather than hand-rolled token verification, `[x]` P1.T03 — Sign in with Apple and Google · **M**

### Community 146 - "[code].tsx"
Cohesion: 0.27
Nodes (11): AcceptInviteScreen(), InviteScreen(), InviteCodeShape, parseInviteLink(), clearPendingInvite(), readPendingInvite(), rememberPendingInvite(), useAcceptInvite() (+3 more)

### Community 147 - "UnlinkTest.php"
Cohesion: 0.08
Nodes (7): CoupleUnlinked, SendUnlinkNotice, NotifyPartnerOfUnlink, Jobs, events & schedules, {closure#10}(), {closure#15}(), {closure#9}()

### Community 148 - "useAuth.tsx"
Cohesion: 0.18
Nodes (17): setTokenReader(), SignInScreen(), fetchMe, mountGate(), Probe(), clearToken(), readToken(), resetTokenCache() (+9 more)

### Community 150 - "SendInviteEmail"
Cohesion: 0.28
Nodes (3): BuildExportForUser, SendInviteEmail, {closure#34}()

### Community 151 - "(tabs)/_layout.tsx"
Cohesion: 0.25
Nodes (9): QUICK_ADD_DESTINATIONS, TAB_ICONS, tabIcon(), TabName, TabsLayout(), QuickAddOption, QuickAddSheet(), QuickAddSheetProps (+1 more)

### Community 152 - "Phase 0 — Foundations"
Cohesion: 0.12
Nodes (13): PingJob, {closure#2}(), Phase 0 — Foundations, `[x]` P0.T01 — Monorepo and Laravel 13 API · **M**, `[x]` P0.T02 — Expo app with Expo Router and TS strict · **M**, `[x]` P0.T03 — Theme tokens and the contrast gate · **M**, `[x]` P0.T05 — Linting and static analysis · **S**, `[x]` P0.T07 — Documentation system · **M** (+5 more)

### Community 153 - "useTheme"
Cohesion: 0.13
Nodes (20): ADR-0019, 9. Implementation contract, haptic(), HAPTIC_STYLE, HapticWeight, PressFeedback, PressWeight, usePressFeedback() (+12 more)

### Community 154 - "Hero"
Cohesion: 0.11
Nodes (18): DatesScreen(), MomentsScreen(), PlannerScreen(), WelcomeScreen(), Hero(), Adding one, Component library, Surfaces (+10 more)

### Community 155 - "index.tsx"
Cohesion: 0.34
Nodes (11): HomeScreen(), UsScreen(), Stat(), CoupleSummary, daysTogether(), firstName(), formatCount(), greeting() (+3 more)

### Community 156 - "AiDriver"
Cohesion: 0.10
Nodes (18): AiDriver, AnthropicDriver, Drivers, Why there is a fake driver *and* laravel/ai's fake, {closure#10}(), {closure#12}(), {closure#13}(), {closure#14}() (+10 more)

### Community 157 - "UsageLoggerTest.php"
Cohesion: 0.11
Nodes (18): {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#17}(), {closure#18}(), {closure#19}(), {closure#2}() (+10 more)

### Community 158 - "SchemaValidatorTest.php"
Cohesion: 0.18
Nodes (20): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#18}() (+12 more)

### Community 159 - "Changelog"
Cohesion: 0.15
Nodes (6): GenerateValidated, SafetyGuard, RunEval, Changelog, PrivacyFilter, filter()

### Community 160 - "PromptRegistryTest.php"
Cohesion: 0.16
Nodes (16): {closure#10}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#17}(), {closure#19}(), {closure#2}() (+8 more)

### Community 161 - "AnthropicDriverTest.php"
Cohesion: 0.15
Nodes (9): TaskAgent, {closure#10}(), {closure#11}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#8}() (+1 more)

### Community 162 - "SchemaValidator"
Cohesion: 0.16
Nodes (3): Output validation, Generation, SchemaValidator

### Community 163 - "Milestones & Countdowns ⏳ - mobile"
Cohesion: 0.22
Nodes (7): Changelog, Known gaps / TODO, Local state, Milestones & Countdowns ⏳ - mobile, Offline behaviour, Tests, Theme usage

### Community 164 - "3. Running it"
Cohesion: 0.50
Nodes (4): 3. Running it, API — Herd serves HTTP; it does **not** run the queue or scheduler, Mobile, Proving the queue and the scheduler actually run

### Community 165 - "TwoCouples"
Cohesion: 0.17
Nodes (16): TwoCouples, {closure#7}(), {closure#8}(), {closure#9}(), aiCases(), aiRoutes(), {closure#10}(), {closure#2}() (+8 more)

### Community 166 - "FakeDriver"
Cohesion: 0.15
Nodes (4): {closure#1}(), FakeDriver, AiResult, AI fixtures

### Community 168 - "FakeDriverTest.php"
Cohesion: 0.22
Nodes (15): {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#18}(), {closure#2}() (+7 more)

### Community 169 - "SafetyVerdict"
Cohesion: 0.20
Nodes (8): SafetyGuard, SafetyState, Concerning, Safe, Unusable, SafetyVerdict, {closure#37}(), ADR-025 — What SafetyGuard says, and who hears it

### Community 174 - "AiRequest"
Cohesion: 0.16
Nodes (9): AiRequest, {closure#9}(), {closure#10}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#7}(), {closure#8}() (+1 more)

### Community 175 - "Illuminate\Contracts\JsonSchema\JsonSchema"
Cohesion: 0.14
Nodes (13): {closure#1}(), {closure#2}(), {closure#1}(), {closure#7}(), {closure#1}(), {closure#19}(), {closure#7}(), {closure#1}() (+5 more)

### Community 176 - "PromptRegistry"
Cohesion: 0.19
Nodes (6): {closure#1}(), PromptRegistry, Prompts, Slots, What goes in a prompt file, {closure#22}()

### Community 178 - "EvalCommandTest.php"
Cohesion: 0.16
Nodes (5): batteries(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 179 - "GenerateValidatedTest.php"
Cohesion: 0.36
Nodes (11): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), generate() (+3 more)

### Community 180 - "TaskSchemas"
Cohesion: 0.26
Nodes (3): TaskSchemas, {closure#5}(), {closure#9}()

### Community 182 - "Billing & Entitlements 🧾"
Cohesion: 0.20
Nodes (10): AI usage (if any), API endpoints, Billing & Entitlements 🧾, Changelog, Data model, Known gaps / TODO, Permissions & privacy, Purpose (+2 more)

### Community 183 - "inventory.test.tsx"
Cohesion: 0.22
Nodes (5): screenFiles(), SCREENS, Status, LAYOUT, TABS

### Community 184 - "AiService"
Cohesion: 0.32
Nodes (3): AiService, GenerationController, ADR-007 — `laravel/ai` over the Anthropic PHP SDK

### Community 185 - "What is real and what is not"
Cohesion: 0.25
Nodes (7): Mobile screens, Screens specified but not built at all, Status key, Things that are real but temporary, What is real and what is not, What the gate actually checks, When you build a feature

### Community 186 - "Bucket List 🪣 - mobile"
Cohesion: 0.29
Nodes (7): Bucket List 🪣 - mobile, Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 187 - "Daily Spark 🔥 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Daily Spark 🔥 - mobile, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage

### Community 188 - "Moments 📸 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Moments 📸 - mobile, Offline behaviour, Tests, Theme usage

### Community 189 - "Shared Planner 🗓️ - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Shared Planner 🗓️ - mobile, Tests, Theme usage

### Community 190 - "Wishlist & Gift Vault 🎁 - mobile"
Cohesion: 0.29
Nodes (7): Changelog, Known gaps / TODO, Local state, Offline behaviour, Tests, Theme usage, Wishlist & Gift Vault 🎁 - mobile

### Community 191 - "CoupleLinked"
Cohesion: 0.33
Nodes (5): CoupleLinked, Business rules, Jobs, events & schedules, Jobs, events & schedules, Jobs, events & schedules

### Community 193 - "resources/prompts/safety_screen/v1.md"
Cohesion: 0.40
Nodes (4): The text, Weigh indirect and euphemistic phrasing, What is not a concern, When you cannot tell

### Community 194 - "safety_screen battery"
Cohesion: 0.40
Nodes (4): How each file is used, safety_screen battery, Shape, Standing gap

### Community 196 - "sign-up.tsx"
Cohesion: 0.83
Nodes (3): detectTimezone(), SignUpScreen(), useRegister()

## Knowledge Gaps
- **17 isolated node(s):** `Ok`, `InvalidOutput`, `SafetyTripped`, `Capped`, `Error` (+12 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1196 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **64 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Couple`, `SocialAuthTest.php`, `SwoonMailable`, `Service Providers & Routing`, `CoupleTest.php`, `UsageLogger`, `PrivacyTest.php`, `InviteController.php`, `AiGeneration`, `UnlinkTest.php`, `ProfileTest.php`, `SendInviteEmail`, `TwoCouples`, `PipelineTest.php`, `SeederTest.php`, `PolicyTest.php`, `AiService`, `UserTest.php`, `AI Platform ✨`, `Changelog`, `BuildDataExport`, `AuthTest.php`, `InviteTest.php`, `CoupleFactory`, `Illuminate\Http\JsonResponse`, `CoupleInvite`, `Illuminate\Http\Request`, `UserResource`?**
  _High betweenness centrality (0.180) - this node is a cross-community bridge._
- **Are the 10 inferred relationships involving `User` (e.g. with `Changelog` and `Data model`) actually correct?**
  _`User` has 10 INFERRED edges - model-reasoned connections that need verification._
- **What connects `Ok`, `InvalidOutput`, `SafetyTripped` to the rest of the system?**
  _17 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App Root & Theme Provider` be split into smaller, more focused modules?**
  _Cohesion score 0.07092198581560284 - nodes in this community are weakly interconnected._
- **Why does `Session log — Swoon` connect `Service Providers & Routing` to `Phase 0 — Foundations`, `CapGuard`, `Button`, `SwoonMailable`?**
  _High betweenness centrality (0.094) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `Couple` (e.g. with `Changelog` and `Data model`) actually correct?**
  _`Couple` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.12643678160919541 - nodes in this community are weakly interconnected._