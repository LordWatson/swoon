import type { ReactNode } from 'react';
import { Animated, Pressable, Text, View } from 'react-native';

import { useTheme, usePressFeedback, type CardTone } from '../theme';
import { GradientFill } from './internal/GradientFill';

export type CardProps = {
  children: ReactNode;
  title?: string;
  /** Small tracked label above the title: "This Friday", "Tonight". */
  overline?: string;
  /** Module emoji, shown beside the title. */
  emoji?: string;
  tone?: CardTone;
  onPress?: () => void;
  /** Marks a card whose contents are private to the viewer (UX rule 9). */
  isPrivate?: boolean;
  testID?: string;
};

const AnimatedPressable = Animated.createAnimatedComponent(Pressable);

/**
 * The standard surface, on either of the app's two card planes.
 *
 * `paper`: white, radius 20, hairline edge, soft plum shadow.
 * `feature`: plum gradient, radius 28, a deeper shadow because a dark surface
 * needs more lift than a white one to read as raised rather than pasted on.
 *
 * In dark mode the shadows flatten and the surface lift does the work instead,
 * which is handled in the theme, not here.
 */
export function Card({
  children,
  title,
  overline,
  emoji,
  tone = 'paper',
  onPress,
  isPrivate = false,
  testID,
}: CardProps) {
  const { colours, radii, spacing, shadows, cardPadding, featurePadding, scaleType } = useTheme();
  const { animatedStyle, onPressIn, onPressOut } = usePressFeedback('card', 'light');

  const feature = tone === 'feature';
  const headingToken = scaleType(feature ? 'title' : 'heading');
  const overlineToken = scaleType('overline');
  const caption = scaleType('caption');

  const ink = feature ? colours.onFeature : colours.ink;
  const muted = feature ? colours.featureMuted : colours.inkMuted;
  const radius = feature ? radii.feature : radii.card;

  const body = (
    <View
      style={[
        {
          backgroundColor: feature ? colours.feature : colours.surface,
          borderRadius: radius,
          padding: feature ? featurePadding : cardPadding,
          // The feature plane carries its own gradient, so an edge on top of
          // it would only muddy it.
          borderWidth: feature ? 0 : 1,
          borderColor: colours.border,
          gap: spacing.sm,
          overflow: 'hidden',
        },
        feature ? shadows.feature : shadows.card,
      ]}
    >
      {feature ? (
        <GradientFill from={colours.featureGradientFrom} to={colours.feature} radius={radius} />
      ) : null}

      {overline ? (
        <Text
          style={{
            color: muted,
            fontSize: overlineToken.size,
            lineHeight: overlineToken.lineHeight,
            fontFamily: overlineToken.family,
            letterSpacing: overlineToken.letterSpacing,
          }}
        >
          {overline}
        </Text>
      ) : null}

      {title ? (
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
          {emoji ? <Text style={{ fontSize: headingToken.size }}>{emoji}</Text> : null}
          <Text
            style={{
              color: ink,
              fontSize: headingToken.size,
              lineHeight: headingToken.lineHeight,
              fontFamily: headingToken.family,
              letterSpacing: headingToken.letterSpacing,
              flex: 1,
            }}
          >
            {title}
          </Text>
        </View>
      ) : null}

      {children}

      {isPrivate ? (
        <Text
          style={{
            color: muted,
            fontSize: caption.size,
            lineHeight: caption.lineHeight,
            fontFamily: caption.family,
            letterSpacing: caption.letterSpacing,
          }}
        >
          🔒 Only you can see this
        </Text>
      ) : null}
    </View>
  );

  if (!onPress) return <View testID={testID}>{body}</View>;

  return (
    <AnimatedPressable
      onPress={onPress}
      onPressIn={onPressIn}
      onPressOut={onPressOut}
      testID={testID}
      accessibilityRole="button"
      accessibilityLabel={title}
      style={animatedStyle}
    >
      {body}
    </AnimatedPressable>
  );
}
