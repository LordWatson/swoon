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
};

/** Scale: 32 / 24 / 20 / 17 (body) / 15 / 13 */
export const typography = {
  display: { size: 32, lineHeight: 38, family: fontFamilies.displayBold, isDisplay: true },
  title: { size: 24, lineHeight: 30, family: fontFamilies.display, isDisplay: true },
  heading: { size: 20, lineHeight: 26, family: fontFamilies.bodyBold, isDisplay: false },
  body: { size: 17, lineHeight: 24, family: fontFamilies.body, isDisplay: false },
  label: { size: 15, lineHeight: 20, family: fontFamilies.bodyMedium, isDisplay: false },
  caption: { size: 13, lineHeight: 18, family: fontFamilies.body, isDisplay: false },
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
  };
}
