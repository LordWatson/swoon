import { Text, View } from 'react-native';

import { useTheme } from '../theme';
import { Button } from './Button';

export type EmptyStateProps = {
  /** Exactly one emoji. */
  emoji: string;
  /** One friendly sentence. Max ~12 words. */
  message: string;
  /** The one button that fixes it. */
  actionLabel: string;
  onAction: () => void;
  testID?: string;
};

/**
 * Every empty state is emoji + one sentence + one button (UX rule 7).
 *
 * The props are deliberately non-optional: an empty state without a way out is
 * the thing this component exists to prevent.
 */
export function EmptyState({ emoji, message, actionLabel, onAction, testID }: EmptyStateProps) {
  const { colours, spacing, scaleType } = useTheme();
  const body = scaleType('body');

  return (
    <View
      testID={testID}
      style={{ alignItems: 'center', gap: spacing.base, paddingVertical: spacing.xxl }}
    >
      <Text style={{ fontSize: 40 }} accessibilityElementsHidden importantForAccessibility="no">
        {emoji}
      </Text>

      <Text
        style={{
          color: colours.inkMuted,
          fontSize: body.size,
          lineHeight: body.lineHeight,
          fontFamily: body.family,
          textAlign: 'center',
          paddingHorizontal: spacing.xl,
        }}
      >
        {message}
      </Text>

      <Button label={actionLabel} onPress={onAction} />
    </View>
  );
}
