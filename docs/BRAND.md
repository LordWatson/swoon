# Brand — Swoon 💞

**Last updated:** 2026-11-05 — design-language uplift: three planes, the
editorial type pair, five-step elevation, and press feedback with haptics.
**Implemented as:** `mobile/src/theme/` (single typed theme, light + dark variants)
**Enforced by:** custom ESLint rule `swoon/no-raw-design-values` — no raw hex, rgb(), or font names outside `mobile/src/theme/`.

> The product name is a placeholder. It lives in exactly one place per side:
> `api/config/swoon.php → 'name'` and `mobile/src/config/app.ts → APP_NAME`.
> Renaming the product means changing those two values, not the codebase.

---

## 1. Personality

Playful, warm, intimate, a little cheeky. **Never** cheesy or corporate.

It should feel like **a shared private notebook with a sense of humour** — not a
productivity tool, not a social network, not a wellness app.

| We are | We are not |
|---|---|
| A private in-joke between two people | A feed, a profile, an audience |
| Warm and a bit silly | Earnest, therapeutic, clinical |
| Celebratory | Scolding, gamified, guilt-tripping |
| Quietly useful | Demanding of daily attention |

## 2. Voice

* **Second person, plural when shared:** "you two", "your week", "both of you".
* **Short sentences.** One idea per line.
* **British spelling** throughout: *realise, colour, favourite, organise, apologise, whilst* (prefer *while*), *travelling, cosy, pyjamas.*
* **Light emoji** — at the end of a sentence, one per sentence maximum.
* **Celebrate, never scold.** Nothing is ever "overdue" or "missed".
* **Sentence case** everywhere, including buttons and headings.

### Voice examples

| Situation | ✅ Say | ❌ Not |
|---|---|---|
| Date confirmed | "Date locked in 🎉 We'll nudge you both the day before." | "Event successfully scheduled." |
| Waiting on partner | "Waiting for Sam to answer 👀" | "Pending partner response" |
| Streak paused | "Your streak's having a lie-in 😴 Pick it back up whenever." | "You broke your 6-day streak!" |
| Empty moments feed | "No moments yet. Go on, bottle one 📸" | "No data available." |
| AI plan ready | "Three ideas, cooked up just for you two ✨" | "Your AI recommendations have been generated." |
| Private item | "Only you can see this 🔒" | "Visibility: PRIVATE" |
| Free-tier cap hit | "That's your plan for this month 💝 More on Together, whenever you fancy." | "Quota exceeded. Upgrade required." |
| Unlink confirm | "This ends your shared space. Your own moments stay with you." | "Are you sure? This action cannot be undone." |

### Words we avoid

*Users, engagement, optimise, leverage, journey (as jargon), score, rank, fail,
missed, overdue, streak broken, you should, you haven't, don't forget to.*

Never imply a partner is falling short. Never compare the two partners.

---

## 3. Colour palette

### 3.1 How the tokens are layered

The founder's twelve brand colours are kept **exactly as specified** and are used as
**fills, surfaces and ink**. Several of them cannot carry body text at WCAG AA (a
saturated Rosé on white is 3.0:1, not 4.5:1), so the theme adds two derived token
families rather than altering the brand:

| Family | Purpose | Example |
|---|---|---|
| **Brand** | Fills, surfaces, ink | `primary` on a button, `surface` on a card |
| **`*Text`** | The same hue when it must be *read* as text or a link | `primaryText` for a Rosé link on cream |
| **`on*`** | The label that sits **on top of** a brand fill | `onPrimary` for the label inside a Rosé pill |

Two utility tokens complete the set: `borderStrong` (interactive control outlines,
3:1) and `focus` (focus rings, 3:1).

**Rule of thumb:** filling a shape → brand token. Writing words on a coloured
shape → `on*`. Writing coloured words on the page → `*Text`.

### 3.1.1 The three planes

Depth is what stops a warm palette reading as flat. The app has exactly three
planes, and nothing is allowed a fourth:

| Plane | Token | What it is |
|---|---|---|
| **Page** | `background` | Cream. The paper the app is printed on. |
| **Card** | `surface` | Paper white, radius 20, hairline `border`, soft lift. |
| **Feature** | `feature` | Deep plum, radius 28, gradient, deep lift. |

**At most one feature plane per screen.** It is the hero, or the one signature
moment — a reveal, a locked-in date. A second one on the same screen means
neither is a feature, and the screen just looks loud.

`feature` is Midnight Plum, the same value as `ink`, which is why it needed no
new accessibility work: a Cream label on it is the 15.04:1 pair the palette
already shipped, read the other way round. Its own ink family is `onFeature`
(body), `featureMuted` (secondary) and `featureAccent` (Butter — the one accent
the plane allows).

In dark mode the page is already plum, so the feature plane cannot go darker.
It **lifts** instead, which is the same substitution dark mode makes for a
shadow (§ 5).

### 3.1.2 Gradients

Two fills are gradients rather than flat colour, because a flat fill has no
sense of light falling on it: the primary CTA (`primaryGradientFrom` → `primary`)
and the feature plane (`featureGradientFrom` → `feature`). Both are one subtle
step, top-lighter.

**A gradient has no single background colour, so text on it must clear AA at
both ends.** The contrast test checks both, and the lighter end is always the
binding one. This is the single way a gradient can quietly break AA, so it is
gated rather than trusted.

### 3.2 Light theme

| Token | Name | Hex | Use |
|---|---|---|---|
| `primary` | Rosé | `#FF5A7A` | Primary buttons, key actions, hearts |
| `primarySoft` | Blush | `#FFE3E8` | Primary-tinted surfaces, selected chips |
| `secondary` | Apricot | `#FFB37A` | Secondary accents, planner, countdowns |
| `highlight` | Butter | `#FFE59E` | Celebrations, streaks, badges |
| `ai` | Lavender | `#B9A7FF` | Anything AI-generated (always marked) |
| `success` | Sage | `#7FCBA8` | Completed, accepted, saved |
| `danger` | Cherry | `#E2243D` | Destructive actions, errors |
| `ink` | Midnight Plum | `#2B1B3D` | Primary text |
| `inkMuted` | Dusk | `#6E5F7E` | Secondary text |
| `background` | Cream | `#FFF8F2` | App background |
| `surface` | Paper | `#FFFFFF` | Cards, sheets |
| `border` | Petal | `#F1E1E6` | Decorative dividers, card borders |
| `borderStrong` | Petal Deep | `#C17990` | Input and control outlines (3:1) |
| `focus` | Rosé Deep | `#E4002C` | Focus rings |
| `primaryText` | Rosé Deep | `#E4002C` | Rosé **as text** / links |
| `secondaryText` | Apricot Deep | `#BC5100` | Apricot **as text** |
| `successText` | Sage Deep | `#347F5C` | Sage **as text** |
| `dangerText` | Cherry Deep | `#DE1D37` | Error **text** |
| `aiText` | Lavender Deep | `#7450FF` | AI label **text** |
| `onPrimary` | — | `#2B1B3D` | Label on a Rosé fill |
| `onSecondary` | — | `#2B1B3D` | Label on an Apricot fill |
| `onHighlight` | — | `#2B1B3D` | Label on a Butter fill |
| `onAi` | — | `#2B1B3D` | Label on a Lavender fill |
| `onSuccess` | — | `#2B1B3D` | Label on a Sage fill |
| `onDanger` | — | `#FFFFFF` | Label on a Cherry fill |
| `feature` | Midnight Plum | `#2B1B3D` | The feature plane (§ 3.1.1) |
| `featureGradientFrom` | Plum Lift | `#412A5A` | Top of the feature gradient |
| `onFeature` | Cream | `#FFF8F2` | Body text on the feature plane |
| `featureMuted` | Plum Mist | `#C3B4CF` | Secondary text on the feature plane |
| `featureAccent` | Butter | `#FFE59E` | The one accent on the feature plane |
| `primaryGradientFrom` | Rosé Light | `#FF7E96` | Top of the primary CTA gradient |

### 3.3 Dark theme

| Token | Hex | Token | Hex |
|---|---|---|---|
| `primary` | `#FF6F8C` | `borderStrong` | `#845DA9` |
| `primarySoft` | `#3A2236` | `focus` | `#FF6F8C` |
| `secondary` | `#FFBE8C` | `primaryText` | `#FF6F8C` |
| `highlight` | `#E8C96A` | `secondaryText` | `#FFBE8C` |
| `ai` | `#C4B5FF` | `successText` | `#8FD3B6` |
| `success` | `#8FD3B6` | `dangerText` | `#FF5C70` |
| `danger` | `#FF5C70` | `aiText` | `#C4B5FF` |
| `ink` | `#F6EEF7` | `onPrimary` … `onDanger` | `#2B1B3D` |
| `inkMuted` | `#B8A9C6` | | |
| `background` | `#1A1024` | | |
| `surface` | `#2B1B3D` | | |
| `border` | `#3D2A4F` | | |
| `feature` | `#412952` | `onFeature` | `#F6EEF7` |
| `featureGradientFrom` | `#4A2F5E` | `featureMuted` | `#B8A9C6` |
| `primaryGradientFrom` | `#FF8CA4` | `featureAccent` | `#E8C96A` |

In dark mode every brand accent is light enough to read directly on the dark
surfaces, so `*Text` equals the accent itself. Because the fills are light, **every**
`on*` label is Midnight Plum — including `onDanger`, which is white in light mode.
This asymmetry is deliberate and is why `on*` tokens exist at all.

### 3.4 Accessibility — verified, not assumed

Every token pair that can carry text was checked against WCAG 2.1:
**4.5:1** for body text (1.4.3), **3:1** for interactive control boundaries and
focus indicators (1.4.11). Derived tokens were solved to a **4.6:1 / 3.1:1**
target so rounding never drops a pair below the line.

Worst-case ratios in the shipped palette:

| Pair | Light | Dark |
|---|---|---|
| `ink` on `background` | 15.04 | 16.17 |
| `inkMuted` on `background` | 5.54 | 8.33 |
| `primaryText` on `background` | 4.60 | 6.91 |
| `onPrimary` on `primary` | 5.27 | 5.96 |
| `onDanger` on `danger` | 4.61 | 5.29 |
| `aiText` on `surface` | 4.85 | 8.59 |
| `borderStrong` on `surface` | 3.28 | 3.10 |
| `onFeature` on `feature` | 15.04 | 12.86 |
| `featureMuted` on the feature gradient | 6.31 | 5.12 |
| `featureAccent` on the feature gradient | 9.95 | 6.99 |
| `onPrimary` across the primary gradient | 5.27 | 5.96 |
| `focus` on `background` | 4.60 | 6.91 |

**Two changes were made to the founder's spec**, both recorded in `docs/DECISIONS.md`:

1. **Cherry darkened `#E5384F` → `#E2243D`** (light theme only). At the original
   value *neither* a white label (4.19:1) nor a plum label (4.19:1) reached AA — a
   dead zone with no accessible label. The darkened shade carries white at 4.61:1.
2. **`border` (Petal) is documented as decorative.** At 1.26:1 on white it cannot
   serve as a control outline, so `borderStrong` was added for inputs, toggles and
   anything interactive. Petal stays as-is for card edges and dividers, which carry
   no information (WCAG 1.4.11 exempts purely decorative boundaries).

The check is a **test, not a note**: `mobile/src/theme/__tests__/contrast.test.ts`
recomputes every pair and fails CI if any regress. Adding a colour token means
adding it to that test.

### 3.5 Partner colours

Each partner gets an identity colour for avatars, "who added this" dots, and their
answers in reveal screens.

* **Partner A** — Rosé
* **Partner B** — Lavender
* Either may swap to **Apricot** or **Sage** in settings. The app prevents both
  partners holding the same colour.

**Never rely on colour alone.** Every partner-attributed element pairs the colour
with an avatar or initial, and a screen-reader label naming the partner. This is
both an accessibility requirement and a practical one — Rosé and Lavender are not
distinguishable to many people with colour vision deficiency.

---

## 4. Typography

| Role | Family | Notes |
|---|---|---|
| Display / headings | **Fraunces** | Soft, characterful serif. Headings and big moments **only** — never body copy, never UI labels. |
| Body / UI | **Nunito** | Rounded, friendly, highly legible. Everything else. |

**Scale:** 44 · 32 · 24 · 20 · 17 (body) · 15 · 13 · 12

| Token | Size | Tracking | Family | Use |
|---|---|---|---|---|
| `hero` | 44 | −1.1 | Fraunces | **One per screen.** The hero on the feature plane |
| `display` | 32 | −0.6 | Fraunces | Celebration moments, year-in-review |
| `title` | 24 | −0.3 | Fraunces | Section titles, sheet titles, feature card titles |
| `heading` | 20 | −0.2 | Nunito | Card titles, list headers |
| `body` | 17 | 0 | Nunito | Default body text |
| `label` | 15 | +0.1 | Nunito | Buttons, chips, form labels |
| `caption` | 13 | +0.1 | Nunito | Timestamps, helper text, "Only you can see this" |
| `overline` | 12 | **+1.6** | Nunito | The tracked micro-label above a title |

### 4.1 Tracking is not optional

Every token carries a `letterSpacing`, and it does more for how expensive the
app looks than any other single value. Large display type set at default
tracking reads loose and cheap; small labels set at default tracking read
cramped. So display sizes go negative and the small ones go positive.

Tracking is in points, so it **scales with the size** — a large accessibility
setting would otherwise leave the hero looking spaced out.

### 4.2 The editorial pair

`overline` + `hero` are what make a screen read like a cover rather than a
header: a small wide-tracked label — "Good evening,", "This Friday",
"Cooked up for you two" — above one large Fraunces moment.

**The overline is tracked, never uppercased.** Uppercase is the obvious move and
§ 2 forbids it; tracking alone carries the editorial weight, which is how most
boutique print does it anyway. `textTransform` appears nowhere in the app, and
a test asserts the overline has none.

**Dynamic type.** Every size scales with the OS text-size setting. Sizes above are
the values at the OS default; the theme exposes them through a scaling helper, and
layouts must survive the largest accessibility size without clipping. Never
disable font scaling, and never set `allowFontScaling={false}`.

---

## 5. Shape

| Element | Radius |
|---|---|
| Cards | 20 |
| Feature plane | 28 |
| Buttons | 999 (pill) |
| Inputs | 14 |
| Sheets | 28 (top corners only) |

### 5.1 Elevation

Five steps, not one. A single shadow reused everywhere is what makes an
interface look like a wireframe that shipped.

| Token | Use | Note |
|---|---|---|
| `none` | Flat | Also every light-mode shadow's dark-mode substitute |
| `subtle` | Chips, badges, inline pills | 4% — barely there, which is the point |
| `card` | The paper plane | 7%, 20 blur |
| `feature` | The plum plane | 22% — a deep surface needs a deeper shadow than a white one, or it looks pasted on rather than raised |
| `button` | The primary CTA only | Tinted **Rosé**, not plum: a coloured button casting a grey shadow is the giveaway that a design system was never looked at on a real screen |

Soft only — low opacity, plum-tinted, generous blur, minimal offset. No hard
drop shadows, no black shadows, no borders doubling as shadows. In dark mode
every shadow flattens and the surface lift does the work instead — **except**
`button`, whose coloured glow still reads on a dark background.

### 5.2 Spacing

**Scale:** 4 · 8 · 12 · 16 · 20 · 24 · 32 · 48.

| Token | Value | Use |
|---|---|---|
| `screenGutter` | 20 | Page edges |
| `cardPadding` | 20 | Inside a card |
| `featurePadding` | 24 | Inside the feature plane, which carries the biggest type |

Space is most of what separates an expensive-looking layout from a cheap one,
and the gutter is where it shows first — which is why it is 20 and not 16.

---

## 6. Motion

* **Springy and short: 150–300 ms.** Nothing in the app should feel heavy.
* **Every press is felt.** A control spring-scales (0.96 for a button, 0.99 for
  a card) and fires a light haptic on the way down. There is **no opacity-fade
  press state anywhere** — that was the single clearest tell that this app
  looked cheaper than it is. One hook owns all of it:
  `mobile/src/theme/feedback.ts`.
* **Haptics are part of the design, not a garnish.** Light on a press, success
  on a real win. A phone that cannot buzz — simulator, some Android hardware,
  Low Power Mode — must never break a button, so every call fails silently
  (ADR-019).
* **Heart "pop"** on likes — a quick overshoot scale.
* **Confetti only for real wins:** date locked in, challenge completed,
  anniversary reached, reveal unlocked. Never for routine saves.
* **Reveal animation** when both partners have answered — a short, celebratory
  unveil. This is the app's signature moment; it is worth the polish.
* **Respect "reduce motion".** When the OS flag is set, every animation becomes a
  cross-fade of ≤100 ms and confetti is replaced by a static celebratory emoji.
  This is checked once in a theme hook, not per component. A press still
  *responds* under reduce motion — it just stops bouncing. Removing the
  feedback entirely would be the worse outcome.
* **One animation API: React Native's own `Animated`.** Reanimated is installed
  and stays for the set pieces that need worklets (the reveal, confetti), but
  press feedback and progress use core `Animated` — see ADR-020 for why.

---

## 7. Iconography

**One rounded icon set used everywhere:** Phosphor Icons, *duotone* weight for
module headers and *bold* for inline UI. No mixing weights within a screen, no
second icon library.

**Emoji are content; icons are UI.** An emoji belongs in a moment's mood, a
challenge's copy, or a module's signature mark. An icon belongs in a tab bar, a
button, or a row affordance. Never use an emoji as a button icon, and never use an
icon where the brand calls for an emoji.

Every icon-only button carries an accessible label.

### Module signatures

| Module | Emoji | Colour |
|---|---|---|
| Dates | 🍷 | Rosé |
| Moments | 📸 | Apricot |
| On This Day | 🕰️ | Butter |
| Planner | 🗓️ | Apricot |
| Challenges | 💌 | Rosé |
| Recap | ✨ | Lavender |
| Insights | 💡 | Lavender |
| Daily Spark | 🔥 | Butter |
| Love Notes | 💕 | Rosé |
| Bucket List | 🪣 | Sage |
| Gift Vault | 🎁 | Apricot |

Modules without a founder-assigned signature inherit one in `MODULES.md`:
Milestones ⏳ Butter · Date Roulette 🎡 Rosé · Date Debrief 💬 Sage ·
Year in Moments 📖 Lavender · Check-in 🌡️ Sage · Love Map 🗺️ Apricot ·
Wishlist ⭐ Apricot.

---

## 8. AI presentation

Anything generated by Claude is **always** visibly marked:

* The **Lavender ✨ `AITag`** component sits above or beside the content.
* It can be **regenerated** and **rated** (thumbs up / down).
* It is **never** presented as a partner's words, and never styled like a partner's
  message.
* AI content uses `ai` as its accent and `aiText` when the AI colour must be read
  as text.

See `docs/UX_RULES.md` rule 6 and `docs/AI_PROMPTS.md`.

---

## 9. Implementation contract

```
mobile/src/theme/
├── colours.ts      # light + dark token maps (the ONLY hex values in the app)
├── typography.ts   # families, scale, tracking, dynamic-type helper
├── shape.ts        # radii, spacing, padding, elevation
├── motion.ts       # durations, springs, press scale, reduce-motion helper
├── feedback.ts     # usePressFeedback() — the spring, the scale, the haptic
├── modules.ts      # per-module emoji + colour signature
├── index.ts        # Theme type, ThemeProvider, useTheme()
└── __tests__/
    ├── contrast.test.ts   # WCAG gate — fails CI on any regression
    └── feedback.test.tsx  # what a press does, including reduce motion
```

Rules, enforced by lint:

1. **No raw hex, `rgb()`, or `hsl()`** outside `mobile/src/theme/colours.ts`.
2. **No font family strings** outside `mobile/src/theme/typography.ts`.
3. **No magic radii or spacing** — use the shape tokens.
4. Components read tokens via `useTheme()`; they never import `colours.ts` directly.
5. A new colour token requires a row in this file **and** a case in the contrast test.
6. A **gradient** token requires a case asserting its text clears AA at *both*
   ends, not just the base colour.
7. **No `opacity` press states.** Pressables use `usePressFeedback()`.
8. **No `textTransform`.** Sentence case is a brand rule (§ 2); tracking is how
   a label gets emphasis.
