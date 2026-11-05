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

export const screenGutter = spacing.base;

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
  card: {
    shadowColor: '#2B1B3D',
    shadowOpacity: 0.06,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 4 },
    elevation: 2,
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
