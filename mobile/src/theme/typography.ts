/**
 * Typography tokens — the ONLY place font family names may appear.
 *
 * Fraunces (soft serif) for display and headings only; Nunito (rounded sans)
 * for everything else. See docs/BRAND.md § 4.
 *
 * Sizes are the values at the OS default text size. Every size scales with the
 * OS setting — never pass allowFontScaling={false}.
 */
import { PixelRatio } from 'react-native';

export const fontFamilies = {
  display: 'Fraunces_600SemiBold',
  displayBold: 'Fraunces_700Bold',
  body: 'Nunito_400Regular',
  bodyMedium: 'Nunito_600SemiBold',
  bodyBold: 'Nunito_700Bold',
} as const;

export type TypeToken = {
  size: number;
  lineHeight: number;
  family: string;
  /** Headings use Fraunces; everything else Nunito. */
  isDisplay: boolean;
  /**
   * Tracking, in points. Large display type needs negative tracking or it
   * reads loose and cheap; small labels need positive tracking or they read
   * cramped. This is the single highest-leverage detail in the whole scale.
   */
  letterSpacing: number;
};

/**
 * Scale: 44 / 32 / 24 / 20 / 17 (body) / 15 / 13 / 12
 *
 * `hero` and `overline` are the editorial pair: one very large Fraunces moment
 * per screen, and a small tracked label above it. Everything between them is
 * the original scale, unchanged.
 */
export const typography = {
  hero: {
    size: 44,
    lineHeight: 46,
    family: fontFamilies.displayBold,
    isDisplay: true,
    letterSpacing: -1.1,
  },
  display: {
    size: 32,
    lineHeight: 37,
    family: fontFamilies.displayBold,
    isDisplay: true,
    letterSpacing: -0.6,
  },
  title: {
    size: 24,
    lineHeight: 29,
    family: fontFamilies.display,
    isDisplay: true,
    letterSpacing: -0.3,
  },
  heading: {
    size: 20,
    lineHeight: 26,
    family: fontFamilies.bodyBold,
    isDisplay: false,
    letterSpacing: -0.2,
  },
  body: { size: 17, lineHeight: 25, family: fontFamilies.body, isDisplay: false, letterSpacing: 0 },
  label: {
    size: 15,
    lineHeight: 20,
    family: fontFamilies.bodyMedium,
    isDisplay: false,
    letterSpacing: 0.1,
  },
  caption: {
    size: 13,
    lineHeight: 18,
    family: fontFamilies.body,
    isDisplay: false,
    letterSpacing: 0.1,
  },
  /**
   * The editorial micro-label: "This Friday", "Tonight", "Your week".
   *
   * Tracked wide and kept SENTENCE CASE. Uppercase would be the obvious move
   * and the brand forbids it (docs/BRAND.md § 2) — tracking alone carries the
   * editorial weight, which is how Aesop and most boutique print do it.
   */
  overline: {
    size: 12,
    lineHeight: 16,
    family: fontFamilies.bodyBold,
    isDisplay: false,
    letterSpacing: 1.6,
  },
} as const satisfies Record<string, TypeToken>;

export type TypographyToken = keyof typeof typography;

/**
 * Scale a token for the OS text-size setting.
 *
 * `getFontScale()` already reflects the user's choice; this caps the multiplier
 * so a very large setting cannot break a layout outright, while still honouring
 * the user's preference well past the point most apps give up. Capping rather
 * than ignoring is the accessible compromise (docs/UX_RULES.md rule 12).
 */
export function scaleType(token: TypographyToken, maxScale = 1.6): TypeToken {
  const t = typography[token];
  const scale = Math.min(PixelRatio.getFontScale(), maxScale);

  return {
    ...t,
    size: Math.round(t.size * scale),
    lineHeight: Math.round(t.lineHeight * scale),
    // Tracking is in points, so it has to scale with the size or a large
    // accessibility setting leaves display type looking loose.
    letterSpacing: Math.round(t.letterSpacing * scale * 100) / 100,
  };
}
