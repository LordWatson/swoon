import { View } from 'react-native';

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
 */
export function ProgressBar({ step, total, label = 'Progress' }: ProgressBarProps) {
  const { colours, radii, spacing } = useTheme();

  const safeTotal = Math.max(total, 1);
  const clampedStep = Math.min(Math.max(step, 0), safeTotal);
  const fraction = clampedStep / safeTotal;

  return (
    <View
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={`${label}, step ${clampedStep} of ${safeTotal}`}
      accessibilityValue={{ min: 0, max: safeTotal, now: clampedStep }}
      style={{
        height: spacing.sm,
        borderRadius: radii.chip,
        backgroundColor: colours.border,
        overflow: 'hidden',
      }}
    >
      <View
        style={{
          width: `${fraction * 100}%`,
          height: '100%',
          backgroundColor: colours.secondary,
          borderRadius: radii.chip,
        }}
      />
    </View>
  );
}
