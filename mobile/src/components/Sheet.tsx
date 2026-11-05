import type { ReactNode } from 'react';
import { Modal, Pressable, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useTheme } from '../theme';

export type SheetProps = {
  visible: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
  testID?: string;
};

/**
 * A bottom sheet. Radius 28 on the top corners only.
 *
 * Used for the quick-add sheet, confirmations and short forms. The backdrop is
 * dismissable and the close button is a real 44 pt labelled control, so the
 * sheet is never a trap for a screen-reader or switch-control user.
 */
export function Sheet({ visible, onClose, title, children, testID }: SheetProps) {
  const { colours, radii, spacing, minTouchTarget, scaleType, shadows } = useTheme();
  const insets = useSafeAreaInsets();
  const titleType = scaleType('title');
  const labelType = scaleType('label');

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
      testID={testID}
      accessibilityViewIsModal
    >
      <Pressable
        onPress={onClose}
        accessibilityRole="button"
        accessibilityLabel="Close"
        style={{ flex: 1, backgroundColor: colours.backdrop }}
      />

      <View
        style={[
          {
            backgroundColor: colours.surface,
            borderTopLeftRadius: radii.sheet,
            borderTopRightRadius: radii.sheet,
            paddingHorizontal: spacing.base,
            paddingTop: spacing.lg,
            // Keep content clear of the home indicator.
            paddingBottom: insets.bottom + spacing.lg,
            gap: spacing.base,
          },
          shadows.sheet,
        ]}
      >
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
          <Text
            style={{
              flex: 1,
              color: colours.ink,
              fontSize: titleType.size,
              lineHeight: titleType.lineHeight,
              fontFamily: titleType.family,
            }}
          >
            {title}
          </Text>

          <Pressable
            onPress={onClose}
            accessibilityRole="button"
            accessibilityLabel="Close"
            hitSlop={8}
            style={{
              minWidth: minTouchTarget,
              minHeight: minTouchTarget,
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <Text
              style={{
                color: colours.primaryText,
                fontSize: labelType.size,
                fontFamily: labelType.family,
              }}
            >
              Close
            </Text>
          </Pressable>
        </View>

        {children}
      </View>
    </Modal>
  );
}
