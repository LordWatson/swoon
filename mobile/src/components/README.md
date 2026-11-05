# Component library

Ten components. Reuse before writing (`docs/UX_RULES.md` rule 15) — a one-off
styled `View` inside a feature folder is a bug, not a shortcut.

Every component reads tokens through `useTheme()`. None of them contains a hex
value, a font name, a magic radius or an `opacity` press state, and ESLint
enforces the first two.

## The design language in one paragraph

Three planes: cream **page**, paper **card**, plum **feature** — at most one
feature per screen. One typographic moment per screen (`hero`), with a tracked
`overline` above it. Five elevation steps, not one. Every press spring-scales
and fires a haptic. The whole of it is specified in `docs/BRAND.md`; this file
is just the parts list.

## Surfaces

| Component | What it is |
|---|---|
| **`Card`** | The standard surface, on either plane. `tone="paper"` (default) or `tone="feature"`. Takes `overline`, `title`, `emoji`, `isPrivate`, and becomes pressable with `onPress`. |
| **`Hero`** | The screen hero: `overline` + 44pt Fraunces `title` + a Butter `accent` line, on the feature plane, with a `children` slot for a stat row. One per screen. Renders a `Card tone="feature"` rather than reimplementing the plane. |
| **`Sheet`** | Bottom sheet, radius 28 on the top corners. Dismissable backdrop and a real 44pt labelled close control. |

## Content

| Component | What it is |
|---|---|
| **`Stat`** | A big Fraunces figure with a tracked label under it. `tone` picks the plane; `accent` draws the figure in Butter. |
| **`AvatarPair`** | Both partners, always. The identity colour is never the only signal — each avatar carries an initial and a screen-reader label. `tone` matches the ring to the plane. |
| **`AITag`** | The Lavender ✨ badge every piece of model output wears. Never optional. |
| **`EmptyState`** | Emoji + one Fraunces sentence + one button. The props are deliberately all required: an empty state with no way out is what this component exists to prevent. |
| **`ProgressBar`** | Position in a multi-step flow, not a percentage. The fill glides rather than jumps. |

## Controls

| Component | What it is |
|---|---|
| **`Button`** | The pill. `primary` (gradient, Rosé-tinted glow, one per screen) / `secondary` / `ghost` / `danger`, at `base` or `large`. Labels come from the `on*` tokens so they always meet AA — never hand-pick a label colour. |
| **`Chip`** | A big tappable choice. Questionnaires and filters are built from these rather than form inputs. A selected chip lifts; an unselected one stays flat. |

## Internal

`internal/GradientFill` is a two-stop vertical gradient used by `Button` and
`Card`. Not exported — screens should not reach for it. It exists because React
Native has no gradient and `react-native-svg` (already here for Phosphor) does
it in nine lines, where `expo-linear-gradient` would be a new dependency.

## Adding one

Rule 15, in order: put it here, match its neighbours' prop conventions, add a
row above, and write a test. For anything with colour, the test belongs in
`src/theme/__tests__/contrast.test.ts` as well — a token with no contrast
assertion is not a token.

## Tests

`__tests__/designLanguage.test.tsx` asserts the promises the design makes that
an edit could quietly break — feature ink on the feature plane, the hero as the
only hero-sized type, tracked-not-uppercased overlines, 44pt targets, AA-safe
button labels. It fires no events: press feedback starts a real animation that
outlives the test, so how a press behaves is asserted against the hook in
`src/theme/__tests__/feedback.test.tsx` instead.
