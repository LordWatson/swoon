import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Card, Hero } from '../../components';
import { useTheme } from '../../theme';

export type PlaceholderScreenProps = {
  overline: string;
  title: string;
  emoji: string;
  /** What this tab will do, in the brand voice. Present tense, no promises. */
  message: string;
  /** The phase that replaces this screen, e.g. "Phase 4". */
  arriving: string;
  testID: string;
};

/**
 * A tab that has its shell but not its feature yet.
 *
 * **It offers no button.** It used to render an `EmptyState`, which was the
 * wrong component: an empty state is for a feature that *works* and has
 * nothing in it, and it requires an action by design because an empty state
 * with no way out is what it exists to prevent (UX rule 7). A feature that
 * does not exist is a different thing, and wiring "Add a moment" to a no-op
 * gave four buttons across four tabs that a person would tap and nothing
 * would happen.
 *
 * So this says plainly what the tab will do and which phase brings it. Every
 * tab is reachable from day one and none of them pretends.
 *
 * Phases 3-6 replace these one at a time, and each replacement should delete
 * its entry from `docs/PLACEHOLDERS.md`.
 */
export function PlaceholderScreen({
  overline,
  title,
  emoji,
  message,
  arriving,
  testID,
}: PlaceholderScreenProps) {
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();

  const body = scaleType('body');
  const caption = scaleType('caption');

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

      <Card overline="Not built yet" title="On its way" emoji={emoji} testID={`${testID}-notice`}>
        <View style={{ gap: spacing.sm }}>
          <Text
            style={{
              color: colours.ink,
              fontSize: body.size,
              lineHeight: body.lineHeight,
              fontFamily: body.family,
            }}
          >
            {message}
          </Text>
          <Text
            style={{
              color: colours.inkMuted,
              fontSize: caption.size,
              lineHeight: caption.lineHeight,
              fontFamily: caption.family,
            }}
          >
            Arriving in {arriving} 🌱
          </Text>
        </View>
      </Card>
    </ScrollView>
  );
}
