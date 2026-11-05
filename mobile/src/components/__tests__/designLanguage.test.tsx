/**
 * The design language, asserted where it can actually regress.
 *
 * These are not snapshot tests — a snapshot of a styled View tells you
 * something changed, never whether it got worse. Each case here is a promise
 * the design makes that a future edit could quietly break: the feature plane
 * uses feature ink, the hero is the only thing allowed to be hero-sized, a
 * press is felt as well as seen, and every control is still reachable.
 *
 * No case here fires an event. Press feedback starts a real spring animation,
 * which outlives the test that started it and breaks the next render — so how
 * a press behaves is asserted in `src/theme/__tests__/feedback.test.tsx`,
 * against the hook, where that logic actually lives.
 *
 * `render` is asynchronous in React Native Testing Library 14, so every case
 * awaits it. Queries come from the render result rather than the global
 * `screen`, which would be ambiguous in the cases that render two trees in
 * order to compare them.
 */
import { cleanup, render } from '@testing-library/react-native';
import type { ReactElement } from 'react';
import { Text } from 'react-native';

import { Button, Card, Hero, Stat } from '..';
import { ThemeProvider, lightTheme, typography } from '../../theme';

async function renderThemed(ui: ReactElement) {
  return render(<ThemeProvider value={lightTheme}>{ui}</ThemeProvider>);
}

// Auto-cleanup does not run for the async `render`, so trees accumulate and
// later queries in the file start failing. Unmounting explicitly keeps each
// case independent.
afterEach(() => {
  cleanup();
});

/** Flatten an RN style prop (object, array, or nested arrays) into one object. */
function flatten(style: unknown): Record<string, unknown> {
  if (Array.isArray(style)) return Object.assign({}, ...style.map(flatten));
  return (style ?? {}) as Record<string, unknown>;
}

function styleOf(node: { props: { style?: unknown } }): Record<string, unknown> {
  return flatten(node.props.style);
}

describe('Hero', () => {
  it('renders the overline, the title and the partner accent', async () => {
    const view = await renderThemed(<Hero overline="Good evening," title="Alex" accent="& Sam" />);

    expect(view.getByText('Good evening,')).toBeTruthy();
    expect(view.getByText('Alex')).toBeTruthy();
    expect(view.getByText('& Sam')).toBeTruthy();
  });

  it('puts the accent in Butter and the title in feature ink', async () => {
    const view = await renderThemed(<Hero title="Alex" accent="& Sam" />);

    expect(styleOf(view.getByText('Alex')).color).toBe(lightTheme.colours.onFeature);
    expect(styleOf(view.getByText('& Sam')).color).toBe(lightTheme.colours.featureAccent);
  });

  it('is the only thing that gets hero-sized type', async () => {
    // If a card title ever reaches hero size, the one-typographic-moment-per-
    // screen rule is gone and the hero stops meaning anything. Asserted as a
    // relationship, not a number: every size here is multiplied by the OS font
    // scale, so the absolute values depend on the device.
    const hero = await renderThemed(<Hero title="Alex" />);
    const heroSize = styleOf(hero.getByText('Alex')).fontSize as number;

    const card = await renderThemed(<Card title="Dinner at Oklava">{null}</Card>);
    const cardSize = styleOf(card.getByText('Dinner at Oklava')).fontSize as number;

    expect(heroSize).toBeGreaterThan(cardSize);
    expect(heroSize / cardSize).toBeCloseTo(typography.hero.size / typography.heading.size, 1);
  });
});

describe('Card', () => {
  it('switches its ink with the plane, so feature text is never plum on plum', async () => {
    const view = await renderThemed(
      <Card tone="feature" title="On the feature plane">
        {null}
      </Card>,
    );

    expect(styleOf(view.getByText('On the feature plane')).color).toBe(
      lightTheme.colours.onFeature,
    );
  });

  it('renders a tracked overline above the title', async () => {
    const view = await renderThemed(
      <Card overline="This Friday" title="Dinner at Oklava">
        {null}
      </Card>,
    );

    const style = styleOf(view.getByText('This Friday'));

    // Tracked wide and SENTENCE CASE — the brand forbids uppercase, so the
    // tracking is the whole of the editorial effect (docs/BRAND.md § 4).
    expect(style.letterSpacing as number).toBeGreaterThan(1);
    expect(style.textTransform).toBeUndefined();
    expect(style.color).toBe(lightTheme.colours.inkMuted);
  });

  it('still says when a card is private', async () => {
    const view = await renderThemed(
      <Card title="Gift idea" isPrivate>
        <Text>A weekend in Margate</Text>
      </Card>,
    );

    expect(view.getByText('🔒 Only you can see this')).toBeTruthy();
  });
});

describe('Stat', () => {
  it('reads as one thing to a screen reader', async () => {
    const view = await renderThemed(<Stat value="214" label="days together" />);

    expect(view.getByLabelText('214 days together')).toBeTruthy();
  });

  it('uses feature ink and the Butter accent on the feature plane', async () => {
    const view = await renderThemed(
      <Stat value="214" label="days together" tone="feature" accent />,
    );

    expect(styleOf(view.getByText('214')).color).toBe(lightTheme.colours.featureAccent);
    expect(styleOf(view.getByText('days together')).color).toBe(lightTheme.colours.featureMuted);
  });
});

describe('Button', () => {
  it('keeps the AA-safe knockout label, never a hand-picked colour', async () => {
    const view = await renderThemed(<Button label="Plan a date" onPress={() => undefined} />);

    expect(styleOf(view.getByText('Plan a date')).color).toBe(lightTheme.colours.onPrimary);
  });

  it('tells assistive tech when it is disabled, and stops responding', async () => {
    const view = await renderThemed(
      <Button label="Plan a date" onPress={() => undefined} disabled testID="cta" />,
    );

    // A disabled control is hidden from the accessibility tree, and the
    // queries honour that by default — which is the behaviour we want.
    const cta = view.getByTestId('cta', { includeHiddenElements: true });

    // Dimmed, but not so far that it stops being legible — a disabled control
    // still has to be readable (docs/UX_RULES.md rule 12).
    const opacity = styleOf(cta).opacity as number;

    expect(cta.props.accessibilityState).toMatchObject({ disabled: true });
    expect(opacity).toBeLessThan(1);
    expect(opacity).toBeGreaterThanOrEqual(0.4);
  });

  // One render per test: the library unmounts the previous tree, so two
  // renders in one case would leave the first unqueryable.
  it('meets the 44pt touch target at the base size', async () => {
    const view = await renderThemed(
      <Button label="Base" onPress={() => undefined} testID="base" />,
    );

    expect(styleOf(view.getByTestId('base')).minHeight).toBeGreaterThanOrEqual(44);
  });

  it('is taller again at the large size, for the one primary action', async () => {
    const view = await renderThemed(
      <Button label="Large" onPress={() => undefined} size="large" testID="large" />,
    );

    expect(styleOf(view.getByTestId('large')).minHeight).toBeGreaterThan(44);
  });
});
