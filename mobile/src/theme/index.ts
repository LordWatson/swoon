/**
 * The Swoon theme: one typed object, light and dark variants.
 *
 * Components read tokens via `useTheme()`. They never import `colours.ts`
 * directly, and no file outside this folder may contain a raw hex value or a
 * font family name — enforced by the `no-restricted-syntax` rule in
 * eslint.config.js. See docs/BRAND.md § 9.
 */
import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { AccessibilityInfo, useColorScheme } from 'react-native';

import { darkColours, lightColours, type ColourTokens } from './colours';
import { durations, pressScale, springs } from './motion';
import {
  cardPadding,
  featurePadding,
  radii,
  screenGutter,
  shadows,
  spacing,
  minTouchTarget,
  type Shadow,
  type ShadowToken,
} from './shape';
import { fontFamilies, scaleType, typography } from './typography';

export type Theme = {
  scheme: 'light' | 'dark';
  colours: ColourTokens;
  typography: typeof typography;
  fontFamilies: typeof fontFamilies;
  scaleType: typeof scaleType;
  radii: typeof radii;
  spacing: typeof spacing;
  screenGutter: number;
  cardPadding: number;
  featurePadding: number;
  minTouchTarget: number;
  /** Shadow TOKENS, not the light-mode literals — dark mode substitutes flats. */
  shadows: Record<ShadowToken, Shadow>;
  durations: typeof durations;
  springs: typeof springs;
  pressScale: typeof pressScale;
  /** True when the OS "reduce motion" setting is on. */
  reduceMotion: boolean;
};

/** Dark mode keeps the same token names, with the shadows flattened. */
const darkShadows: Record<ShadowToken, Shadow> = {
  none: shadows.none,
  subtle: shadows.none,
  card: shadows.none,
  feature: shadows.none,
  // The one exception: a coloured glow under the primary CTA reads on a dark
  // background where a plum shadow would not, and it is what keeps the one
  // saturated thing on the screen looking lit rather than pasted on.
  button: shadows.button,
  sheet: shadows.none,
};

function buildTheme(scheme: 'light' | 'dark', reduceMotion: boolean): Theme {
  return {
    scheme,
    colours: scheme === 'dark' ? darkColours : lightColours,
    typography,
    fontFamilies,
    scaleType,
    radii,
    spacing,
    screenGutter,
    cardPadding,
    featurePadding,
    minTouchTarget,
    // A shadow is invisible on a dark background, so dark mode drops it and
    // relies on the surface/background lift instead (docs/BRAND.md § 5).
    shadows: scheme === 'dark' ? darkShadows : shadows,
    durations,
    springs,
    pressScale,
    reduceMotion,
  };
}

export const lightTheme = buildTheme('light', false);

const ThemeContext = createContext<Theme>(lightTheme);

export const ThemeProvider = ThemeContext.Provider;

export function useTheme(): Theme {
  return useContext(ThemeContext);
}

/**
 * Builds the theme from the OS colour scheme and reduce-motion setting.
 *
 * Call this once at the app root and feed it into ThemeProvider; everything
 * else reads `useTheme()`.
 */
export function useSystemTheme(): Theme {
  const scheme = useColorScheme() === 'dark' ? 'dark' : 'light';
  const [reduceMotion, setReduceMotion] = useState(false);

  useEffect(() => {
    let active = true;

    AccessibilityInfo.isReduceMotionEnabled().then((enabled) => {
      if (active) setReduceMotion(enabled);
    });

    const sub = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduceMotion);

    return () => {
      active = false;
      sub.remove();
    };
  }, []);

  // Memoised because this object is the context value: a new identity on every
  // render would re-render every component that reads useTheme(), including on
  // renders where neither the colour scheme nor reduce-motion changed.
  return useMemo(() => buildTheme(scheme, reduceMotion), [scheme, reduceMotion]);
}

/**
 * Animation timing that already respects "reduce motion".
 *
 * Use this instead of reading `durations`/`springs` directly, so the
 * accessibility flag is applied in one place rather than remembered at every
 * call site.
 */
export function useMotion(): { duration: number; useSpring: boolean; confetti: boolean } {
  const { reduceMotion, durations: d } = useTheme();

  return reduceMotion
    ? { duration: d.instant, useSpring: false, confetti: false }
    : { duration: d.base, useSpring: true, confetti: true };
}

/**
 * Which of the app's two card planes something sits on. Lives in the theme
 * rather than in Card, because components that are not cards (AvatarPair,
 * Stat) still have to know which plane they are drawn on.
 */
export type CardTone = 'paper' | 'feature';

export type { ColourTokens, Shadow, ShadowToken };
export { lightColours, darkColours };
export { moduleSignatures, type ModuleKey, type ModuleSignature } from './modules';
export { typography, fontFamilies, scaleType, type TypographyToken } from './typography';
export {
  radii,
  spacing,
  shadows,
  screenGutter,
  cardPadding,
  featurePadding,
  minTouchTarget,
} from './shape';
export { durations, springs, pressScale, type Celebration } from './motion';
export {
  usePressFeedback,
  haptic,
  type HapticWeight,
  type PressWeight,
  type PressFeedback,
} from './feedback';
