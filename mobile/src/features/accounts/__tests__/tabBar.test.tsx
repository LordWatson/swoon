/**
 * The tab bar.
 *
 * The regression: no `tabBarIcon` was configured for any tab, so React
 * Navigation drew its placeholder glyph and the bar showed five crosses. The
 * assertion that matters is that **every tab has an icon** — one missing is
 * what a person sees.
 *
 * Driven off the layout's own config rather than by mounting a navigator: the
 * thing that was wrong was the configuration, and mounting Expo Router in Jest
 * tests the navigator rather than the decision.
 */
import fs from 'node:fs';
import path from 'node:path';

const LAYOUT = fs.readFileSync(
  path.join(__dirname, '..', '..', '..', 'app', '(tabs)', '_layout.tsx'),
  'utf8',
);

/** The five tabs, in the order the bar shows them (UX rule 14). */
const TABS = ['index', 'dates', 'moments', 'planner', 'us'] as const;

describe('every tab', () => {
  it.each(TABS)('declares an icon for %s', (name) => {
    // Without this the bar renders a placeholder glyph, which is what showed
    // up as an X.
    const screen = new RegExp(`name="${name}"[\\s\\S]{0,200}?tabBarIcon`);

    expect(LAYOUT).toMatch(screen);
  });

  it.each(TABS)('declares a text label for %s', (name) => {
    // Colour alone marks the active tab, so the label is not decoration —
    // it is the non-colour signal (docs/BRAND.md § 3.5).
    const screen = new RegExp(`name="${name}"[\\s\\S]{0,200}?tabBarLabel`);

    expect(LAYOUT).toMatch(screen);
  });

  it('has exactly five, and no more', () => {
    // Five tabs and a centre "+" is the whole navigation. A sixth is a
    // product decision, not a convenience.
    const declared = LAYOUT.match(/<Tabs\.Screen/g) ?? [];

    expect(declared).toHaveLength(TABS.length);
  });
});

describe('the icons themselves', () => {
  it('uses Phosphor, not emoji', () => {
    // Emoji are content; icons are UI, and a tab bar is UI (BRAND.md § 7).
    expect(LAYOUT).toContain("from 'phosphor-react-native'");
  });

  it('uses one weight throughout', () => {
    // Mixing weights within a screen is explicitly out, and `bold` is what
    // § 7 specifies for inline UI.
    const weights = [...LAYOUT.matchAll(/weight="(\w+)"/g)].map((match) => match[1]);

    expect(weights.length).toBeGreaterThan(0);
    expect(new Set(weights)).toEqual(new Set(['bold']));
  });

  it('gives the centre + a real icon and an accessible label', () => {
    // It was a "+" text character. An icon-only control without a label is an
    // accessibility bug (UX rule 12), and this one is the main action.
    expect(LAYOUT).toContain('<Plus');
    expect(LAYOUT).toContain('accessibilityLabel="Add something"');
  });
});

describe('the quick-add sheet', () => {
  it('sends every option somewhere', () => {
    // All four used to close the sheet and do nothing, which leaves a person
    // unsure whether they mis-tapped.
    for (const option of ['moment', 'plan', 'note', 'date-idea']) {
      expect(LAYOUT).toContain(`${option.includes('-') ? `'${option}'` : option}:`);
    }

    expect(LAYOUT).toContain('router.push(QUICK_ADD_DESTINATIONS[option])');
  });
});
