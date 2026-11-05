/**
 * Swoon colour tokens — the ONLY place hex values may appear in this app.
 *
 * Every pair that can carry text meets WCAG AA (4.5:1 body text, 3:1 for
 * interactive boundaries and focus rings). This is verified by
 * `src/theme/__tests__/contrast.test.ts`, which fails CI on any regression.
 *
 * Three families, documented in docs/BRAND.md § 3.1:
 *
 *   brand    — fills, surfaces and ink            (`primary` on a button)
 *   `*Text`  — the same hue when it must be READ  (`primaryText` for a link)
 *   `on*`    — the label that sits ON a fill      (`onPrimary` inside a pill)
 *
 * Rule of thumb: filling a shape → brand token. Words on a coloured shape →
 * `on*`. Coloured words on the page → `*Text`.
 *
 * Adding a token means adding a row to docs/BRAND.md AND a case to the
 * contrast test. A token with no contrast assertion is not a token.
 */

export type ColourTokens = {
  // Brand — fills and surfaces
  primary: string;
  primarySoft: string;
  secondary: string;
  highlight: string;
  ai: string;
  success: string;
  danger: string;
  ink: string;
  inkMuted: string;
  background: string;
  surface: string;
  border: string;

  // Interactive boundaries (3:1)
  borderStrong: string;
  focus: string;

  /** Modal/sheet backdrop. 8-digit hex: the alpha is part of the token. */
  backdrop: string;

  // Accents used AS TEXT (4.5:1 on background and surface)
  primaryText: string;
  secondaryText: string;
  successText: string;
  dangerText: string;
  aiText: string;

  /**
   * The feature plane — a deep plum surface used inside the cream app for the
   * hero and for signature moments. This is the second plane: cream page,
   * paper card, plum feature. Nothing else gets to be plum.
   */
  feature: string;
  /** Top of the feature gradient. The bottom is `feature` itself. */
  featureGradientFrom: string;
  /** Top of the primary CTA gradient. The bottom is `primary` itself. */
  primaryGradientFrom: string;

  // Labels that sit ON a brand fill (4.5:1 against that fill)
  onPrimary: string;
  onSecondary: string;
  onHighlight: string;
  onAi: string;
  onSuccess: string;
  onDanger: string;
  /** Body text on the feature plane. Must clear AA across its whole gradient. */
  onFeature: string;
  /** Secondary text on the feature plane. */
  featureMuted: string;
  /** The one accent allowed on the feature plane — Butter, for the warm detail. */
  featureAccent: string;
};

export const lightColours: ColourTokens = {
  primary: '#FF5A7A', // Rosé
  primarySoft: '#FFE3E8', // Blush
  secondary: '#FFB37A', // Apricot
  highlight: '#FFE59E', // Butter
  ai: '#B9A7FF', // Lavender
  success: '#7FCBA8', // Sage
  // Cherry, darkened from the original #E5384F: at that value neither a white
  // nor an ink label reached AA (both 4.19:1) — a fill with no accessible
  // label. See docs/DECISIONS.md ADR-004.
  danger: '#E2243D',
  ink: '#2B1B3D', // Midnight Plum
  inkMuted: '#6E5F7E', // Dusk
  background: '#FFF8F2', // Cream
  surface: '#FFFFFF', // Paper
  border: '#F1E1E6', // Petal — DECORATIVE dividers only (1.26:1)

  borderStrong: '#C17990', // Petal Deep — inputs, toggles, controls
  focus: '#E4002C', // Rosé Deep — focus rings
  backdrop: '#2B1B3D73', // Midnight Plum at 45%

  primaryText: '#E4002C',
  secondaryText: '#BC5100',
  successText: '#347F5C',
  dangerText: '#DE1D37',
  aiText: '#7450FF',

  // The feature plane. `feature` is Midnight Plum itself — the same value as
  // `ink`, which is why it needed no new verification: a cream label on it is
  // the 15:1 pair the palette already shipped, read the other way round.
  feature: '#2B1B3D', // Midnight Plum
  featureGradientFrom: '#412A5A', // Plum Lift — the light falling on the top edge
  primaryGradientFrom: '#FF7E96', // Rosé Light

  onPrimary: '#2B1B3D',
  onSecondary: '#2B1B3D',
  onHighlight: '#2B1B3D',
  onAi: '#2B1B3D',
  onSuccess: '#2B1B3D',
  onDanger: '#FFFFFF',
  onFeature: '#FFF8F2', // Cream
  featureMuted: '#C3B4CF', // Plum Mist
  featureAccent: '#FFE59E', // Butter
};

export const darkColours: ColourTokens = {
  primary: '#FF6F8C',
  primarySoft: '#3A2236',
  secondary: '#FFBE8C',
  highlight: '#E8C96A',
  ai: '#C4B5FF',
  success: '#8FD3B6',
  danger: '#FF5C70',
  ink: '#F6EEF7',
  inkMuted: '#B8A9C6',
  background: '#1A1024',
  surface: '#2B1B3D',
  border: '#3D2A4F',

  borderStrong: '#845DA9',
  focus: '#FF6F8C',
  backdrop: '#0D0612A6', // near-black plum at 65% - a dark sheet needs more

  // Every dark accent already clears 4.5:1 on the dark surfaces, so the
  // readable form of each hue is the accent itself.
  primaryText: '#FF6F8C',
  secondaryText: '#FFBE8C',
  successText: '#8FD3B6',
  dangerText: '#FF5C70',
  aiText: '#C4B5FF',

  // The fills are light in dark mode, so EVERY knockout label is plum —
  // including onDanger, which is white in light mode. That asymmetry is the
  // whole reason these tokens exist.
  // In dark mode the page is already plum, so the feature plane cannot be
  // darker — it lifts instead, which is the same trick dark mode uses in place
  // of a shadow (docs/BRAND.md § 5).
  feature: '#412952',
  featureGradientFrom: '#4A2F5E',
  primaryGradientFrom: '#FF8CA4',

  onPrimary: '#2B1B3D',
  onSecondary: '#2B1B3D',
  onHighlight: '#2B1B3D',
  onAi: '#2B1B3D',
  onSuccess: '#2B1B3D',
  onDanger: '#2B1B3D',
  onFeature: '#F6EEF7',
  featureMuted: '#B8A9C6',
  featureAccent: '#E8C96A',
};

/** Partner identity colours. Never used alone — always with an avatar. */
export const partnerColourKeys = ['primary', 'ai', 'secondary', 'success'] as const;

export type PartnerColourKey = (typeof partnerColourKeys)[number];

/**
 * The four partner colours by the name the API uses, mapped to theme tokens.
 *
 * The server stores `rose` / `lavender` / `apricot` / `sage` (docs/BRAND.md
 * § 3.5) and the theme knows them as `primary` / `ai` / `secondary` /
 * `success`. The translation lives here because this is the file that owns
 * what the colours *are* — a screen inventing its own mapping is how two
 * screens end up disagreeing about which colour someone is.
 */
export const partnerColourTokens = {
  rose: 'primary',
  lavender: 'ai',
  apricot: 'secondary',
  sage: 'success',
} as const satisfies Record<string, PartnerColourKey>;

export type PartnerColourName = keyof typeof partnerColourTokens;

/**
 * A partner's colour as a theme token.
 *
 * Falls back rather than throwing: a null colour is a real state (nobody has
 * chosen yet) and an unrecognised one means the API moved ahead of the app,
 * neither of which should blank out an avatar.
 */
export function partnerColourToken(
  name: string | null | undefined,
  fallback: PartnerColourKey = 'primary',
): PartnerColourKey {
  if (name !== null && name !== undefined && name in partnerColourTokens) {
    return partnerColourTokens[name as PartnerColourName];
  }

  return fallback;
}
