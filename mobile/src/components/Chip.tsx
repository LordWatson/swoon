import { Pressable, Text } from 'react-native';

import { useTheme } from '../theme';

export type ChipProps = {
  label: string;
  selected?: boolean;
  onPress?: () => void;
  emoji?: string;
  testID?: string;
};

/**
 * A big, tappable choice. The questionnaire and filter surfaces are built from
 * these rather than form inputs (UX rule 3).
 *
 * Selected state uses `primarySoft` with `ink` — 13.1:1 — so a selected chip is
 * never harder to read than an unselected one.
 */
export function Chip({ label, selected = false, onPress, emoji, testID }: ChipProps) {
  const { colours, radii, spacing, minTouchTarget, scaleType } = useTheme();
  const type = scaleType('label');

  return (
    <Pressable
      onPress={onPress}
      disabled={!onPress}
      testID={testID}
      accessibilityRole={onPress ? 'button' : 'text'}
      accessibilityState={{ selected }}
      accessibilityLabel={label}
      style={({ pressed }) => ({
        backgroundColor: selected ? colours.primarySoft : colours.surface,
        borderColor: selected ? colours.primary : colours.borderStrong,
        borderWidth: selected ? 2 : 1,
        borderRadius: radii.chip,
        minHeight: minTouchTarget,
        paddingHorizontal: spacing.base,
        paddingVertical: spacing.sm,
        justifyContent: 'center',
        opacity: pressed ? 0.85 : 1,
      })}
    >
      <Text
        style={{
          color: colours.ink,
          fontSize: type.size,
          lineHeight: type.lineHeight,
          fontFamily: type.family,
        }}
      >
        {emoji ? `${emoji} ${label}` : label}
      </Text>
    </Pressable>
  );
}
