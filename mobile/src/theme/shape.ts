/**
 * Shape tokens: radii, spacing and shadows. See docs/BRAND.md § 5.
 *
 * Soft, plum-tinted shadows only. In dark mode a shadow is invisible, so the
 * theme substitutes a one-step surface lift instead (`elevation` below).
 */

export const radii = {
  card: 20,
  button: 999, // pill
  input: 14,
  sheet: 28, // top corners only
  chip: 999,
  avatar: 999,
  /** The feature plane. Larger than a card, so the two planes read apart. */
  feature: 28,
} as const;

/** Spacing scale: 4 · 8 · 12 · 16 · 20 · 24 · 32 · 48. Screen gutter is 16. */
export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  base: 16,
  lg: 20,
  xl: 24,
  xxl: 32,
  xxxl: 48,
} as const;

/**
 * Screen gutter — 20, not 16.
 *
 * Space is most of what separates an expensive-looking layout from a cheap
 * one, and the gutter is where it shows first. `cardPadding` and `featurePadding`
 * follow the same logic: generous, and consistent, so nothing has to be judged
 * per component.
 */
export const screenGutter = spacing.lg;

/** Inside a card. */
export const cardPadding = spacing.lg;

/** Inside the feature plane, which carries the biggest type and needs the most air. */
export const featurePadding = spacing.xl;

/** Minimum touch target (docs/UX_RULES.md rule 12). */
export const minTouchTarget = 44;

export type Shadow = {
  shadowColor: string;
  shadowOpacity: number;
  shadowRadius: number;
  shadowOffset: { width: number; height: number };
  elevation: number;
};

/**
 * Light-mode shadows. Plum-tinted, low opacity, generous blur, minimal offset —
 * never black, never hard.
 */
export const shadows = {
  none: {
    shadowColor: 'transparent',
    shadowOpacity: 0,
    shadowRadius: 0,
    shadowOffset: { width: 0, height: 0 },
    elevation: 0,
  },
  /** A barely-there lift. Chips, inline pills, anything small. */
  subtle: {
    shadowColor: '#2B1B3D',
    shadowOpacity: 0.04,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
    elevation: 1,
  },
  card: {
    shadowColor: '#2B1B3D',
    shadowOpacity: 0.07,
    shadowRadius: 20,
    shadowOffset: { width: 0, height: 6 },
    elevation: 3,
  },
  /**
   * The feature plane sits highest of the three. A deep surface needs a deeper
   * shadow than a white one or it looks pasted on rather than raised.
   */
  feature: {
    shadowColor: '#2B1B3D',
    shadowOpacity: 0.22,
    shadowRadius: 32,
    shadowOffset: { width: 0, height: 14 },
    elevation: 10,
  },
  /**
   * Under the primary CTA, tinted Rosé rather than plum — a coloured button
   * casting a grey shadow is the giveaway that a design system was never
   * looked at on a real screen.
   */
  button: {
    shadowColor: '#FF5A7A',
    shadowOpacity: 0.34,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 8 },
    elevation: 6,
  },
  sheet: {
    shadowColor: '#2B1B3D',
    shadowOpacity: 0.1,
    shadowRadius: 28,
    shadowOffset: { width: 0, height: -2 },
    elevation: 8,
  },
} as const satisfies Record<string, Shadow>;

export type ShadowToken = keyof typeof shadows;
