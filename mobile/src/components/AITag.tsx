import { Text, View } from 'react-native';

import { useTheme } from '../theme';

export type AITagProps = {
  /** Optional qualifier, e.g. "Date ideas". Keep it short. */
  label?: string;
};

/**
 * The Lavender ✨ tag that marks AI-generated content.
 *
 * Every piece of model output in the app wears this (UX rule 6). It is never
 * optional and AI content is never styled like a partner's message — the whole
 * point is that a couple can always tell which words are theirs.
 */
export function AITag({ label = 'AI' }: AITagProps) {
  const { colours, radii, spacing, shadows, scaleType } = useTheme();
  const type = scaleType('overline');

  return (
    <View
      accessible
      accessibilityRole="text"
      accessibilityLabel={`${label} generated`}
      style={{
        flexDirection: 'row',
        alignSelf: 'flex-start',
        alignItems: 'center',
        gap: spacing.xs,
        backgroundColor: colours.ai,
        borderRadius: radii.chip,
        paddingHorizontal: spacing.md,
        paddingVertical: spacing.xs + 2,
        ...shadows.subtle,
      }}
    >
      <Text style={{ fontSize: type.size }}>✨</Text>
      <Text
        style={{
          // onAi, not ink: in dark mode the Lavender fill is light, so the
          // label must be the plum knockout to stay readable.
          color: colours.onAi,
          fontSize: type.size,
          lineHeight: type.lineHeight,
          fontFamily: type.family,
          letterSpacing: type.letterSpacing,
        }}
      >
        {label}
      </Text>
    </View>
  );
}
