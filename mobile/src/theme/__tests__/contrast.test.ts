/**
 * WCAG contrast gate for the Swoon palette.
 *
 * This test is the reason docs/BRAND.md can promise AA compliance. It
 * recomputes every token pair that can carry text or form an interactive
 * boundary, in BOTH themes, and fails if any drops below its threshold.
 *
 * Adding a colour token means adding a case here. A token with no assertion is
 * not a token — see docs/BRAND.md § 9 rule 5.
 *
 * Thresholds (WCAG 2.1):
 *   4.5:1 — body text                                   (1.4.3 AA)
 *   3.0:1 — interactive boundaries and focus indicators (1.4.11 AA)
 *
 * `border` (Petal) is deliberately absent from the 3:1 checks: it is a
 * DECORATIVE divider, which 1.4.11 exempts. Interactive outlines use
 * `borderStrong`. See docs/DECISIONS.md ADR-004.
 */
import { darkColours, lightColours, type ColourTokens } from '../colours';

/** Relative luminance per WCAG 2.1 definition. */
function luminance(hex: string): number {
  const h = hex.replace('#', '');
  const channels = [0, 2, 4].map((i) => {
    const c = parseInt(h.slice(i, i + 2), 16) / 255;
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}

function contrast(a: string, b: string): number {
  const [la, lb] = [luminance(a), luminance(b)];

  return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}

type Pair = [fg: keyof ColourTokens, bg: keyof ColourTokens, label: string];

/** Text pairs — 4.5:1. */
const TEXT_PAIRS: Pair[] = [
  ['ink', 'background', 'body text on app background'],
  ['ink', 'surface', 'body text on card'],
  ['ink', 'primarySoft', 'text on primary-tinted surface'],
  ['inkMuted', 'background', 'secondary text on background'],
  ['inkMuted', 'surface', 'secondary text on card'],
  ['primaryText', 'background', 'primary link on background'],
  ['primaryText', 'surface', 'primary link on card'],
  ['secondaryText', 'background', 'secondary accent text on background'],
  ['secondaryText', 'surface', 'secondary accent text on card'],
  ['successText', 'background', 'success text on background'],
  ['successText', 'surface', 'success text on card'],
  ['dangerText', 'background', 'error text on background'],
  ['dangerText', 'surface', 'error text on card'],
  ['aiText', 'background', 'AI text on background'],
  ['aiText', 'surface', 'AI text on card'],
  // The feature plane. Checked against the FLAT token here; the gradient it is
  // actually drawn with is checked separately below, because text has to clear
  // AA at both ends of a gradient, not just the darker one.
  ['onFeature', 'feature', 'body text on the feature plane'],
  ['featureMuted', 'feature', 'secondary text on the feature plane'],
  ['featureAccent', 'feature', 'Butter accent on the feature plane'],
];

/** Knockout labels sitting ON a brand fill — 4.5:1. */
const ON_FILL_PAIRS: Pair[] = [
  ['onPrimary', 'primary', 'label on primary button'],
  ['onSecondary', 'secondary', 'label on secondary fill'],
  ['onHighlight', 'highlight', 'label on celebration/badge'],
  ['onAi', 'ai', 'label on AI tag'],
  ['onSuccess', 'success', 'label on success pill'],
  ['onDanger', 'danger', 'label on danger button'],
];

/**
 * Gradient fills — text must clear AA at BOTH ends.
 *
 * A gradient has no single background colour, so the end with the least
 * contrast sets the floor. Checking only the base token would let a too-light
 * top edge through, which is the one way a gradient can quietly break AA.
 */
const GRADIENT_PAIRS: [
  fg: keyof ColourTokens,
  from: keyof ColourTokens,
  to: keyof ColourTokens,
  label: string,
][] = [
  ['onFeature', 'featureGradientFrom', 'feature', 'body text across the feature gradient'],
  ['featureMuted', 'featureGradientFrom', 'feature', 'secondary text across the feature gradient'],
  ['featureAccent', 'featureGradientFrom', 'feature', 'Butter accent across the feature gradient'],
  ['onPrimary', 'primaryGradientFrom', 'primary', 'button label across the primary gradient'],
];

/** Interactive boundaries and focus rings — 3:1. */
const NON_TEXT_PAIRS: Pair[] = [
  ['borderStrong', 'background', 'control outline on background'],
  ['borderStrong', 'surface', 'control outline on card'],
  ['focus', 'background', 'focus ring on background'],
  ['focus', 'surface', 'focus ring on card'],
];

const THEMES: [name: string, tokens: ColourTokens][] = [
  ['light', lightColours],
  ['dark', darkColours],
];

describe.each(THEMES)('%s theme', (_name, tokens) => {
  it.each(TEXT_PAIRS)('body text: %s on %s (%s) meets AA 4.5:1', (fg, bg) => {
    expect(contrast(tokens[fg], tokens[bg])).toBeGreaterThanOrEqual(4.5);
  });

  it.each(ON_FILL_PAIRS)('on-fill label: %s on %s (%s) meets AA 4.5:1', (fg, bg) => {
    expect(contrast(tokens[fg], tokens[bg])).toBeGreaterThanOrEqual(4.5);
  });

  it.each(NON_TEXT_PAIRS)('non-text: %s on %s (%s) meets AA 3:1', (fg, bg) => {
    expect(contrast(tokens[fg], tokens[bg])).toBeGreaterThanOrEqual(3);
  });

  it.each(GRADIENT_PAIRS)(
    'gradient: %s over %s→%s (%s) meets AA 4.5:1 at both ends',
    (fg, from, to) => {
      expect(contrast(tokens[fg], tokens[from])).toBeGreaterThanOrEqual(4.5);
      expect(contrast(tokens[fg], tokens[to])).toBeGreaterThanOrEqual(4.5);
    },
  );

  it('keeps the feature plane distinct from the card plane', () => {
    // Three planes only work if they are actually distinguishable. This is not
    // a WCAG threshold — it is the design promise that a feature card never
    // looks like a paper one.
    expect(tokens.feature).not.toBe(tokens.surface);
    expect(contrast(tokens.feature, tokens.surface)).toBeGreaterThan(1.1);
  });

  it('defines every token in both themes', () => {
    expect(Object.keys(tokens).sort()).toEqual(Object.keys(lightColours).sort());
  });

  it('has a well-formed hex for every token', () => {
    // 6 digits, or 8 where the alpha is part of the token (backdrop).
    const malformed = Object.entries(tokens).filter(
      ([, value]) => !/^#[0-9A-F]{6}(?:[0-9A-F]{2})?$/.test(value),
    );

    expect(malformed).toEqual([]);
  });
});

describe('contrast maths', () => {
  // Guards the helper itself: a broken luminance function would make every
  // assertion above pass vacuously.
  it('computes the known extremes correctly', () => {
    expect(contrast('#FFFFFF', '#000000')).toBeCloseTo(21, 0);
    expect(contrast('#FFFFFF', '#FFFFFF')).toBeCloseTo(1, 5);
  });

  it('is symmetric', () => {
    expect(contrast('#FF5A7A', '#FFFFFF')).toBeCloseTo(contrast('#FFFFFF', '#FF5A7A'), 10);
  });

  it('rejects the pre-fix Cherry, proving the gate actually bites', () => {
    // #E5384F was the original Cherry. Neither knockout reached AA against it,
    // which is why `danger` is #E2243D. If this ever passes, the gate is broken.
    expect(contrast('#FFFFFF', '#E5384F')).toBeLessThan(4.5);
    expect(contrast('#2B1B3D', '#E5384F')).toBeLessThan(4.5);
  });
});
