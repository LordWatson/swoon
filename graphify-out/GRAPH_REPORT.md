# Graph Report - Smitten  (2026-11-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 489 nodes · 664 edges · 77 communities (26 shown, 51 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 1 edges (avg confidence: 0.85)
- Token cost: 56,683 input · 1,043 output

## Community Hubs (Navigation)
- CI Environment Variables
- App Root & Theme Provider
- Home Screen & UI Components
- User Model & Core Config
- Database Migrations
- Expo Runtime Dependencies
- API HTTP Client
- Service Providers & Routing
- Expo Package Manifest
- Mobile Dev Dependencies
- Colour Tokens & Contrast Tests
- Composer Scripts
- Mobile NPM Scripts
- CI Workflow Triggers
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
- Test Autoload Mapping
- Laravel Package Discovery
- Module Docs Hook
- Base Controller
- Expo Device Module
- Expo Image Module
- Expo Linking Module
- Expo Splash Screen
- Expo Status Bar
- Phosphor Icons
- Gesture Handler
- React Native SVG
- React Native Web
- React Native Worklets
- TanStack Query

## God Nodes (most connected - your core abstractions)
1. `useTheme()` - 20 edges
2. `with` - 15 edges
3. `react-native` - 14 edges
4. `scripts` - 13 edges
5. `scripts` - 13 edges
6. `HomeScreen()` - 10 edges
7. `require-dev` - 10 edges
8. `require` - 9 edges
9. `User` - 8 edges
10. `env` - 8 edges

## Surprising Connections (you probably didn't know these)
- `buildTheme()` --indirect_call--> `scaleType()`  [INFERRED]
  mobile/src/theme/index.ts → mobile/src/theme/typography.ts
- `RootLayout()` --calls--> `ThemeProvider`  [EXTRACTED]
  mobile/src/app/_layout.tsx → mobile/src/theme/index.ts
- `RootLayout()` --calls--> `useSystemTheme()`  [EXTRACTED]
  mobile/src/app/_layout.tsx → mobile/src/theme/index.ts
- `HomeScreen()` --calls--> `fetchHealth()`  [EXTRACTED]
  mobile/src/app/index.tsx → mobile/src/api/client.ts
- `HomeScreen()` --calls--> `AITag()`  [EXTRACTED]
  mobile/src/app/index.tsx → mobile/src/components/AITag.tsx

## Import Cycles
- None detected.

## Communities (77 total, 51 thin omitted)

### Community 0 - "CI Environment Variables"
Cohesion: 0.05
Nodes (50): name, runs-on, steps, if, name, runs-on, steps, AI_DRIVER (+42 more)

### Community 1 - "App Root & Theme Provider"
Cohesion: 0.09
Nodes (32): queryClient, RootLayout(), buildTheme(), darkShadows, darkTheme, lightTheme, Theme, ThemeContext (+24 more)

### Community 2 - "Home Screen & UI Components"
Cohesion: 0.16
Nodes (26): fetchHealth(), HomeScreen(), AITag(), AITagProps, AvatarPair(), AvatarPairProps, Partner, Button() (+18 more)

### Community 3 - "User Model & Core Config"
Cohesion: 0.10
Nodes (3): User, UserFactory, DatabaseSeeder

### Community 4 - "Database Migrations"
Cohesion: 0.14
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+1 more)

### Community 5 - "Expo Runtime Dependencies"
Cohesion: 0.10
Nodes (20): dependencies, expo-constants@~58.0.15, @expo-google-fonts/fraunces@^0.4.1, @expo-google-fonts/nunito@^0.4.2, expo-router@~58.0.30, expo-symbols@~58.0.4, expo-system-ui@~58.0.7, react@19.3.0 (+12 more)

### Community 6 - "API HTTP Client"
Cohesion: 0.14
Nodes (11): ApiError, apiRequest(), HealthResponse, isRecord(), readToken(), RequestOptions, safeParse(), TokenReader (+3 more)

### Community 7 - "Service Providers & Routing"
Cohesion: 0.18
Nodes (4): AppServiceProvider, ModuleServiceProvider, GET /health(), GET /()

### Community 8 - "Expo Package Manifest"
Cohesion: 0.13
Nodes (14): expo-font@~58.0.11, expo-glass-effect@~58.0.5, @expo/ui@~58.0.27, expo-web-browser@~58.0.7, react-native-screens@~4.30.1, main, name, private (+6 more)

### Community 9 - "Mobile Dev Dependencies"
Cohesion: 0.13
Nodes (15): devDependencies, jest@^30.5.2, jest-expo@^58.0.14, prettier@^3.9.10, @testing-library/react-native@^14.1.0, @types/jest@^30.0.0, @types/react@~19.3.0, typescript@~6.0.3 (+7 more)

### Community 10 - "Colour Tokens & Contrast Tests"
Cohesion: 0.16
Nodes (13): darkColours, lightColours, PartnerColourKey, partnerColourKeys, ADR-0004, contrast(), luminance(), NON_TEXT_PAIRS (+5 more)

### Community 11 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, analyse, check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd (+5 more)

### Community 12 - "Mobile NPM Scripts"
Cohesion: 0.15
Nodes (13): scripts, android, check, format, format:check, ios, lint, lint:fix (+5 more)

### Community 13 - "CI Workflow Triggers"
Cohesion: 0.18
Nodes (10): concurrency, cancel-in-progress, group, name, on, pull_request, push, permissions (+2 more)

### Community 14 - "ESLint Configuration"
Cohesion: 0.18
Nodes (10): { defineConfig }, expoConfig, noRawDesignValues, prettierConfig, eslint@^9.39.5, eslint-config-expo@^58.0.4, eslint-config-prettier@^10.1.8, eslint (+2 more)

### Community 15 - "PHP Dev Dependencies"
Cohesion: 0.20
Nodes (10): require-dev, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 16 - "Composer Package Metadata"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 17 - "PHP Runtime Dependencies"
Cohesion: 0.22
Nodes (9): require, laravel/ai, laravel/framework, laravel/sanctum, laravel/tinker, league/flysystem-aws-s3-v3, php, resend/resend-laravel (+1 more)

### Community 18 - "TypeScript Configuration"
Cohesion: 0.22
Nodes (8): compilerOptions, paths, strict, types, extends, include, @/assets/*, expo/tsconfig.base

### Community 20 - "Composer Script Descriptions"
Cohesion: 0.25
Nodes (8): scripts-descriptions, analyse, check, dev, lint, lint:check, setup, test

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

### Community 31 - "Test Autoload Mapping"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 32 - "Laravel Package Discovery"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **3 isolated node(s):** `ADR-0004`, `ADR-0004`, `expo/tsconfig.base`
  These have ≤1 connection - possible missing edges. (Counts symbols only; 222 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **51 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `react-native` connect `Home Screen & UI Components` to `Expo Package Manifest`, `App Root & Theme Provider`, `Expo Runtime Dependencies`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **What connects `ADR-0004`, `ADR-0004`, `expo/tsconfig.base` to the rest of the system?**
  _3 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `CI Environment Variables` be split into smaller, more focused modules?**
  _Cohesion score 0.05142857142857143 - nodes in this community are weakly interconnected._
- **Why does `dependencies` connect `Expo Runtime Dependencies` to `Expo Device Module`, `Expo Image Module`, `Expo Linking Module`, `Expo Splash Screen`, `Expo Package Manifest`, `Expo Status Bar`, `Phosphor Icons`, `Gesture Handler`, `React Native SVG`, `React Native Web`, `React Native Worklets`, `TanStack Query`, `Expo App Config`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Should `App Root & Theme Provider` be split into smaller, more focused modules?**
  _Cohesion score 0.08534850640113797 - nodes in this community are weakly interconnected._
- **Why does `react` connect `Home Screen & UI Components` to `Expo Package Manifest`, `App Root & Theme Provider`, `Expo Runtime Dependencies`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Should `User Model & Core Config` be split into smaller, more focused modules?**
  _Cohesion score 0.09686609686609686 - nodes in this community are weakly interconnected._