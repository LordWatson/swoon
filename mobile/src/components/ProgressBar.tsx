import { useEffect, useState } from 'react';
import { Animated, View, type LayoutChangeEvent } from 'react-native';

import { useTheme } from '../theme';

export type ProgressBarProps = {
  /** 1-based current step. */
  step: number;
  total: number;
  /** Describes what is progressing, for screen readers. */
  label?: string;
};

/**
 * Position in a multi-step flow (UX rule 3).
 *
 * Shows position, not a percentage — "question 2 of 5" is what a person wants
 * to know. Uses `secondary` (Apricot) so it never competes with the primary
 * action for attention.
 *
 * The fill glides to its new width rather than jumping. It is a small thing,
 * but it is one of the few moments in a questionnaire where the app can show
 * any craft, and a bar that snaps looks like a web page from 2012.
 *
 * Width cannot run on the native driver, so this one animation is on the JS
 * thread. For a single 200 ms move on step change that is imperceptible, and
 * it keeps the whole app on one animation API (see `src/theme/feedback.ts`).
 */
export function ProgressBar({ step, total, label = 'Progress' }: ProgressBarProps) {
  const { colours, radii, spacing, springs, durations, reduceMotion } = useTheme();

  const safeTotal = Math.max(total, 1);
  const clampedStep = Math.min(Math.max(step, 0), safeTotal);
  const fraction = clampedStep / safeTotal;

  // The track is measured so the fill can animate in points: a percentage
  // string is not an animatable value.
  const [trackWidth, setTrackWidth] = useState(0);
  const [fillWidth] = useState(() => new Animated.Value(0));

  useEffect(() => {
    const toValue = trackWidth * fraction;

    const animation = reduceMotion
      ? Animated.timing(fillWidth, {
          toValue,
          duration: durations.instant,
          useNativeDriver: false,
        })
      : Animated.spring(fillWidth, {
          toValue,
          damping: springs.gentle.damping,
          stiffness: springs.gentle.stiffness,
          mass: springs.gentle.mass,
          useNativeDriver: false,
        });

    animation.start();

    return () => animation.stop();
  }, [durations.instant, fillWidth, fraction, reduceMotion, springs.gentle, trackWidth]);

  return (
    <View
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={`${label}, step ${clampedStep} of ${safeTotal}`}
      accessibilityValue={{ min: 0, max: safeTotal, now: clampedStep }}
      onLayout={(event: LayoutChangeEvent) => setTrackWidth(event.nativeEvent.layout.width)}
      style={{
        height: spacing.sm,
        borderRadius: radii.chip,
        backgroundColor: colours.border,
        overflow: 'hidden',
      }}
    >
      <Animated.View
        style={{
          width: fillWidth,
          height: '100%',
          backgroundColor: colours.secondary,
          borderRadius: radii.chip,
        }}
      />
    </View>
  );
}
