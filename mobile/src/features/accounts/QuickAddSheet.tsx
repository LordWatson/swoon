import { Text, View } from 'react-native';

import { Button, Sheet } from '../../components';
import { useTheme } from '../../theme';

export type QuickAddOption = 'moment' | 'plan' | 'note' | 'date-idea';

export type QuickAddSheetProps = {
  visible: boolean;
  onClose: () => void;
  onChoose: (option: QuickAddOption) => void;
};

/**
 * The centre "+" sheet. **Exactly four options** (UX rule 14).
 *
 * Four is the whole design: it is the number a person can read without
 * deciding, and the list is deliberately closed. A fifth option is a product
 * decision, not a convenience — which is why the options are a const array
 * with a test asserting its length rather than something each caller passes in.
 */
const OPTIONS: { key: QuickAddOption; label: string; emoji: string }[] = [
  { key: 'moment', label: 'A moment', emoji: '📸' },
  { key: 'plan', label: 'Something to plan', emoji: '🗓️' },
  { key: 'note', label: 'A love note', emoji: '💕' },
  { key: 'date-idea', label: 'A date idea', emoji: '🍷' },
];

export function QuickAddSheet({ visible, onClose, onChoose }: QuickAddSheetProps) {
  const { colours, spacing, scaleType } = useTheme();
  const caption = scaleType('caption');

  return (
    <Sheet visible={visible} onClose={onClose} title="Add something" testID="quick-add-sheet">
      <View style={{ gap: spacing.md }}>
        {OPTIONS.map((option) => (
          <Button
            key={option.key}
            label={option.label}
            emoji={option.emoji}
            variant="secondary"
            onPress={() => onChoose(option.key)}
            testID={`quick-add-${option.key}`}
          />
        ))}

        <Text
          style={{
            color: colours.inkMuted,
            fontSize: caption.size,
            lineHeight: caption.lineHeight,
            fontFamily: caption.family,
            letterSpacing: caption.letterSpacing,
            textAlign: 'center',
          }}
        >
          Only the two of you will ever see it 🔒
        </Text>
      </View>
    </Sheet>
  );
}

export { OPTIONS as QUICK_ADD_OPTIONS };
