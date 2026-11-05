import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '../theme';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';

export type ButtonProps = {
  label: string;
  onPress: () => void;
  variant?: ButtonVariant;
  disabled?: boolean;
  loading?: boolean;
  /** Emoji shown before the label. Emoji are content; icons are UI. */
  emoji?: string;
  /** Overrides the label for screen readers when the label alone is ambiguous. */
  accessibilityLabel?: string;
  testID?: string;
};

/**
 * The pill button.
 *
 * `primary` is the one-per-screen action (UX rule 1): a `primary` pill, bottom,
 * thumb-reachable. Colours come from the `on*` tokens so the label always
 * meets AA against its fill — never hand-pick a label colour here.
 */
export function Button({
  label,
  onPress,
  variant = 'primary',
  disabled = false,
  loading = false,
  emoji,
  accessibilityLabel,
  testID,
}: ButtonProps) {
  const { colours, radii, spacing, minTouchTarget, scaleType } = useTheme();
  const type = scaleType('label');

  const fills: Record<ButtonVariant, { bg: string; fg: string; border?: string }> = {
    primary: { bg: colours.primary, fg: colours.onPrimary },
    secondary: { bg: colours.primarySoft, fg: colours.ink },
    ghost: { bg: 'transparent', fg: colours.primaryText, border: colours.borderStrong },
    danger: { bg: colours.danger, fg: colours.onDanger },
  };

  const { bg, fg, border } = fills[variant];
  const inactive = disabled || loading;

  return (
    <Pressable
      onPress={onPress}
      disabled={inactive}
      testID={testID}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel ?? label}
      accessibilityState={{ disabled: inactive, busy: loading }}
      style={({ pressed }) => [
        styles.base,
        {
          backgroundColor: bg,
          borderRadius: radii.button,
          minHeight: minTouchTarget,
          paddingHorizontal: spacing.xl,
          borderWidth: border ? 1 : 0,
          borderColor: border,
          // A disabled control still needs to be legible, so dim modestly.
          opacity: inactive ? 0.5 : pressed ? 0.85 : 1,
        },
      ]}
    >
      {loading ? (
        <ActivityIndicator color={fg} />
      ) : (
        <View style={styles.row}>
          {emoji ? <Text style={styles.emoji}>{emoji}</Text> : null}
          <Text
            style={{
              color: fg,
              fontSize: type.size,
              lineHeight: type.lineHeight,
              fontFamily: type.family,
            }}
            numberOfLines={1}
          >
            {label}
          </Text>
        </View>
      )}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: { alignItems: 'center', justifyContent: 'center' },
  row: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  emoji: { fontSize: 16 },
});
