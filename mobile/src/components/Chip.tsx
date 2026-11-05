import { Animated, Pressable, Text } from 'react-native';

import { useTheme, usePressFeedback } from '../theme';

export type ChipProps = {
  label: string;
  selected?: boolean;
  onPress?: () => void;
  emoji?: string;
  testID?: string;
};

/**
 * A big, tappable choice. The questionnaire and filter surfaces are built from
 * these rather than form inputs (UX rule 3).
 *
 * Selected state uses `primarySoft` with `ink` — 13.1:1 — so a selected chip is
 * never harder to read than an unselected one.
 */
const AnimatedPressable = Animated.createAnimatedComponent(Pressable);

export function Chip({ label, selected = false, onPress, emoji, testID }: ChipProps) {
  const { colours, radii, spacing, shadows, minTouchTarget, scaleType } = useTheme();
  const { animatedStyle, onPressIn, onPressOut } = usePressFeedback('button', 'light');
  const type = scaleType('label');

  return (
    <AnimatedPressable
      onPress={onPress}
      onPressIn={onPress ? onPressIn : undefined}
      onPressOut={onPress ? onPressOut : undefined}
      disabled={!onPress}
      testID={testID}
      accessibilityRole={onPress ? 'button' : 'text'}
      accessibilityState={{ selected }}
      accessibilityLabel={label}
      style={[
        {
          backgroundColor: selected ? colours.primarySoft : colours.surface,
          borderColor: selected ? colours.primary : colours.borderStrong,
          borderWidth: selected ? 2 : 1,
          borderRadius: radii.chip,
          minHeight: minTouchTarget,
          paddingHorizontal: spacing.lg,
          paddingVertical: spacing.sm,
          justifyContent: 'center',
        },
        // A selected chip lifts off the page; an unselected one stays flat, so
        // the difference is felt as well as seen.
        selected ? shadows.subtle : null,
        animatedStyle,
      ]}
    >
      <Text
        style={{
          color: colours.ink,
          fontSize: type.size,
          lineHeight: type.lineHeight,
          fontFamily: type.family,
          letterSpacing: type.letterSpacing,
        }}
      >
        {emoji ? `${emoji} ${label}` : label}
      </Text>
    </AnimatedPressable>
  );
}
