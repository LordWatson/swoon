import { ActivityIndicator, Animated, Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme, usePressFeedback } from '../theme';
import { GradientFill } from './internal/GradientFill';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
export type ButtonSize = 'base' | 'large';

export type ButtonProps = {
  label: string;
  onPress: () => void;
  variant?: ButtonVariant;
  /** `large` is the one-per-screen primary action; everything else is `base`. */
  size?: ButtonSize;
  disabled?: boolean;
  loading?: boolean;
  /** Emoji shown before the label. Emoji are content; icons are UI. */
  emoji?: string;
  /** Overrides the label for screen readers when the label alone is ambiguous. */
  accessibilityLabel?: string;
  testID?: string;
};

const AnimatedPressable = Animated.createAnimatedComponent(Pressable);

/**
 * The pill button.
 *
 * `primary` is the one-per-screen action (UX rule 1): a `primary` pill, bottom,
 * thumb-reachable. Colours come from the `on*` tokens so the label always
 * meets AA against its fill — never hand-pick a label colour here.
 *
 * Three things make it feel like a button rather than a coloured rectangle: a
 * gradient so it catches light, a Rosé-tinted shadow rather than a grey one,
 * and a spring scale with a haptic on press instead of an opacity fade. All
 * three come from the theme, so they are consistent and they respect
 * "reduce motion" without this component knowing about it.
 */
export function Button({
  label,
  onPress,
  variant = 'primary',
  size = 'base',
  disabled = false,
  loading = false,
  emoji,
  accessibilityLabel,
  testID,
}: ButtonProps) {
  const { colours, radii, spacing, shadows, minTouchTarget, scaleType } = useTheme();
  const { animatedStyle, onPressIn, onPressOut } = usePressFeedback('button', 'light');
  const type = scaleType('label');

  const fills: Record<
    ButtonVariant,
    { bg: string; fg: string; border?: string; gradientFrom?: string; lifted?: boolean }
  > = {
    primary: {
      bg: colours.primary,
      fg: colours.onPrimary,
      gradientFrom: colours.primaryGradientFrom,
      lifted: true,
    },
    secondary: { bg: colours.primarySoft, fg: colours.ink },
    ghost: { bg: 'transparent', fg: colours.primaryText, border: colours.borderStrong },
    danger: { bg: colours.danger, fg: colours.onDanger },
  };

  const { bg, fg, border, gradientFrom, lifted } = fills[variant];
  const inactive = disabled || loading;
  const height = size === 'large' ? 56 : minTouchTarget;

  return (
    <AnimatedPressable
      onPress={onPress}
      onPressIn={inactive ? undefined : onPressIn}
      onPressOut={inactive ? undefined : onPressOut}
      disabled={inactive}
      testID={testID}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel ?? label}
      accessibilityState={{ disabled: inactive, busy: loading }}
      style={[
        styles.base,
        {
          backgroundColor: bg,
          borderRadius: radii.button,
          minHeight: height,
          paddingHorizontal: size === 'large' ? spacing.xxl : spacing.xl,
          borderWidth: border ? 1.5 : 0,
          borderColor: border,
          // A disabled control still needs to be legible, so dim modestly.
          opacity: inactive ? 0.45 : 1,
        },
        // The glow belongs to the primary action only, and only while it is
        // actually actionable — a shadow under a dead button looks broken.
        lifted && !inactive ? shadows.button : null,
        animatedStyle,
      ]}
    >
      {gradientFrom && !inactive ? (
        <GradientFill from={gradientFrom} to={bg} radius={radii.button} />
      ) : null}

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
              letterSpacing: type.letterSpacing,
            }}
            numberOfLines={1}
          >
            {label}
          </Text>
        </View>
      )}
    </AnimatedPressable>
  );
}

const styles = StyleSheet.create({
  base: { alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  row: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  emoji: { fontSize: 17 },
});
