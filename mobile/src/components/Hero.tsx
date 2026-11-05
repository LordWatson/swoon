import type { ReactNode } from 'react';
import { Text, View } from 'react-native';

import { useTheme } from '../theme';
import { Card } from './Card';

export type HeroProps = {
  /** Small tracked label above the title: "Good evening,", "Your week". */
  overline?: string;
  /** The big Fraunces moment. One per screen. */
  title: string;
  /**
   * A second line in Butter, for the half of the couple that is not you:
   * `title="Alex"  accent="& Sam"`. Omit it on screens that are not about the
   * two of you.
   */
  accent?: string;
  /** A stat row, an avatar pair, a countdown — whatever this screen leads on. */
  children?: ReactNode;
};

/**
 * The screen hero, on the feature plane.
 *
 * This is the app's one piece of typographic theatre: 44pt Fraunces with
 * negative tracking on deep plum, a tracked overline above it, and Butter for
 * the second name. It is deliberately the only place that type gets this big —
 * a hero on every card is just loud.
 *
 * At most one per screen. It renders a `Card` with `tone="feature"` rather than
 * reimplementing the plane, so the gradient, radius and shadow stay in one
 * place.
 */
export function Hero({ overline, title, accent, children }: HeroProps) {
  const { colours, spacing, scaleType } = useTheme();

  const hero = scaleType('hero');
  const overlineToken = scaleType('overline');

  return (
    <Card tone="feature">
      {overline ? (
        <Text
          style={{
            color: colours.featureMuted,
            fontSize: overlineToken.size,
            lineHeight: overlineToken.lineHeight,
            fontFamily: overlineToken.family,
            letterSpacing: overlineToken.letterSpacing,
          }}
        >
          {overline}
        </Text>
      ) : null}

      <View style={{ marginTop: spacing.xs }}>
        <Text
          style={{
            color: colours.onFeature,
            fontSize: hero.size,
            lineHeight: hero.lineHeight,
            fontFamily: hero.family,
            letterSpacing: hero.letterSpacing,
          }}
        >
          {title}
        </Text>

        {accent ? (
          <Text
            style={{
              color: colours.featureAccent,
              fontSize: hero.size,
              lineHeight: hero.lineHeight,
              fontFamily: hero.family,
              letterSpacing: hero.letterSpacing,
            }}
          >
            {accent}
          </Text>
        ) : null}
      </View>

      {children ? <View style={{ marginTop: spacing.base }}>{children}</View> : null}
    </Card>
  );
}
