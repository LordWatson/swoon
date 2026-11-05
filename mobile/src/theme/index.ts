/**
 * The Swoon theme: one typed object, light and dark variants.
 *
 * Components read tokens via `useTheme()`. They never import `colours.ts`
 * directly, and no file outside this folder may contain a raw hex value or a
 * font family name — enforced by the `no-restricted-syntax` rule in
 * eslint.config.js. See docs/BRAND.md § 9.
 */
import { createContext, useContext, useEffect, useState } from 'react';
import { AccessibilityInfo, useColorScheme } from 'react-native';

import { darkColours, lightColours, type ColourTokens } from './colours';
import { durations, springs } from './motion';
import {
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
  minTouchTarget: number;
  /** Shadow TOKENS, not the light-mode literals — dark mode substitutes flats. */
  shadows: Record<ShadowToken, Shadow>;
  durations: typeof durations;
  springs: typeof springs;
  /** True when the OS "reduce motion" setting is on. */
  reduceMotion: boolean;
};

/** Dark mode keeps the same token names, with the shadows flattened. */
const darkShadows: Record<ShadowToken, Shadow> = {
  none: shadows.none,
  card: shadows.none,
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
    minTouchTarget,
    // A shadow is invisible on a dark background, so dark mode drops it and
    // relies on the surface/background lift instead (docs/BRAND.md § 5).
    shadows: scheme === 'dark' ? darkShadows : shadows,
    durations,
    springs,
    reduceMotion,
  };
}

export const lightTheme = buildTheme('light', false);
export const darkTheme = buildTheme('dark', false);

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

  return buildTheme(scheme, reduceMotion);
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

export type { ColourTokens, Shadow, ShadowToken };
export { lightColours, darkColours };
export { moduleSignatures, type ModuleKey, type ModuleSignature } from './modules';
export { typography, fontFamilies, scaleType, type TypographyToken } from './typography';
export { radii, spacing, shadows, screenGutter, minTouchTarget } from './shape';
export { durations, springs, type Celebration } from './motion';
