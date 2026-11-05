/**
 * Motion tokens. Springy and short — 150–300 ms. See docs/BRAND.md § 6.
 *
 * Everything here must degrade when the OS "reduce motion" flag is set:
 * animations become a ≤100 ms cross-fade and confetti is replaced by a static
 * celebratory emoji (docs/UX_RULES.md rule 12). Use `useMotion()` from
 * `src/theme` rather than reading these directly, so the flag is applied once.
 */

export const durations = {
  instant: 100,
  quick: 150,
  base: 200,
  relaxed: 300,
} as const;

export const springs = {
  /** Default for most transitions. */
  gentle: { damping: 18, stiffness: 180, mass: 1 },
  /** The heart "pop" on a like — a quick overshoot. */
  pop: { damping: 10, stiffness: 320, mass: 0.7 },
  /** The reveal when both partners have answered. The app's signature moment. */
  reveal: { damping: 14, stiffness: 220, mass: 0.9 },
  /**
   * Press-down on a control. Fast and well damped: a control should feel like
   * it takes your finger, not like it wobbles. This replaced an `opacity: 0.85`
   * press state, which is the single clearest tell of a cheap-feeling app.
   */
  press: { damping: 24, stiffness: 450, mass: 0.6 },
} as const;

/**
 * How far a control scales when pressed.
 *
 * Small on purpose. A big surface travelling as far as a small one looks
 * rubbery, so a card moves roughly a quarter of what a button does.
 */
export const pressScale = {
  button: 0.96,
  card: 0.99,
} as const;

export type SpringToken = keyof typeof springs;
export type DurationToken = keyof typeof durations;

/** Confetti is for real wins only: date locked, challenge done, anniversary, reveal. */
export const celebrations = ['dateLocked', 'challengeDone', 'anniversary', 'reveal'] as const;

export type Celebration = (typeof celebrations)[number];
