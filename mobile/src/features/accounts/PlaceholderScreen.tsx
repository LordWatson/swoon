import { ScrollView, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { EmptyState, Hero } from '../../components';
import { useTheme } from '../../theme';

export type PlaceholderScreenProps = {
  overline: string;
  title: string;
  emoji: string;
  message: string;
  actionLabel: string;
  testID: string;
};

/**
 * A tab that has its shell but not its feature yet.
 *
 * Every tab is reachable from day one, with a hero and a real `EmptyState`
 * rather than a blank screen — an empty state with no way out is what
 * `EmptyState` exists to prevent (UX rule 7), and a tab that renders nothing
 * looks broken rather than early.
 *
 * Phases 3–6 replace these one at a time.
 */
export function PlaceholderScreen({
  overline,
  title,
  emoji,
  message,
  actionLabel,
  testID,
}: PlaceholderScreenProps) {
  const { colours, spacing, screenGutter } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <ScrollView
      testID={testID}
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxxl * 2,
        gap: spacing.lg,
      }}
    >
      <Hero overline={overline} title={title} />

      <View>
        <EmptyState
          emoji={emoji}
          message={message}
          actionLabel={actionLabel}
          onAction={() => undefined}
        />
      </View>
    </ScrollView>
  );
}
