import type { ReactNode } from 'react';
import { Pressable, Text, View } from 'react-native';

import { useTheme } from '../theme';

export type CardProps = {
  children: ReactNode;
  title?: string;
  /** Module emoji, shown beside the title. */
  emoji?: string;
  onPress?: () => void;
  /** Marks a card whose contents are private to the viewer (UX rule 9). */
  isPrivate?: boolean;
  testID?: string;
};

/**
 * The standard surface. Radius 20, soft plum shadow in light mode, flat in
 * dark (a shadow is invisible there, so the surface lift does the work).
 */
export function Card({ children, title, emoji, onPress, isPrivate = false, testID }: CardProps) {
  const { colours, radii, spacing, shadows, scaleType } = useTheme();
  const heading = scaleType('heading');
  const caption = scaleType('caption');

  const body = (
    <View
      style={[
        {
          backgroundColor: colours.surface,
          borderRadius: radii.card,
          padding: spacing.base,
          borderWidth: 1,
          borderColor: colours.border,
          gap: spacing.sm,
        },
        shadows.card,
      ]}
    >
      {title ? (
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
          {emoji ? <Text style={{ fontSize: heading.size }}>{emoji}</Text> : null}
          <Text
            style={{
              color: colours.ink,
              fontSize: heading.size,
              lineHeight: heading.lineHeight,
              fontFamily: heading.family,
              flex: 1,
            }}
          >
            {title}
          </Text>
        </View>
      ) : null}

      {children}

      {isPrivate ? (
        <Text
          style={{
            color: colours.inkMuted,
            fontSize: caption.size,
            lineHeight: caption.lineHeight,
            fontFamily: caption.family,
          }}
        >
          🔒 Only you can see this
        </Text>
      ) : null}
    </View>
  );

  if (!onPress) return <View testID={testID}>{body}</View>;

  return (
    <Pressable
      onPress={onPress}
      testID={testID}
      accessibilityRole="button"
      accessibilityLabel={title}
      style={({ pressed }) => ({ opacity: pressed ? 0.9 : 1 })}
    >
      {body}
    </Pressable>
  );
}
