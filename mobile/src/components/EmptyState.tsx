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
  // Fraunces, not muted body copy: an empty state is the first thing a couple
  // sees in a module, so it should look composed rather than apologetic.
  const message_ = scaleType('title');
  const hero = scaleType('hero');

  return (
    <View
      testID={testID}
      style={{ alignItems: 'center', gap: spacing.base, paddingVertical: spacing.xxxl }}
    >
      <Text
        style={{ fontSize: hero.size }}
        accessibilityElementsHidden
        importantForAccessibility="no"
      >
        {emoji}
      </Text>

      <Text
        style={{
          color: colours.ink,
          fontSize: message_.size,
          lineHeight: message_.lineHeight,
          fontFamily: message_.family,
          letterSpacing: message_.letterSpacing,
          textAlign: 'center',
          paddingHorizontal: spacing.base,
        }}
      >
        {message}
      </Text>

      <View style={{ marginTop: spacing.sm }}>
        <Button label={actionLabel} onPress={onAction} />
      </View>
    </View>
  );
}
